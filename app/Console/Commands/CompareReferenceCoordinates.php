<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Console\Command;

class CompareReferenceCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compare:coordinates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compares the WWFF database coordinates to corrected ones';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = resource_path('ohff-coordinates.csv');
        $handle = fopen($path, "r");
        
        if ($handle !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $reference = Reference::where('reference', $data[0])->first();
                if (array_key_exists('1', $data) && array_key_exists('2', $data)) {
                    $distance = $this->getDistanceBetweenPoints($reference->location->getLat(), $reference->location->getLng(), $data[1], $data[2]);
                    if ($distance['kilometers'] > 5) {
                        dump($reference->reference . ';' . $reference->name . ';' . round($distance['kilometers'], 2));
                    }
                }
            }
            fclose($handle);
        }

        return Command::SUCCESS;
    }

    /**
     * Calculates the distance between two points, given their
     * latitude and longitude, and returns an array of values
     * of the most common distance units
     *
     * @param  float $lat1 Latitude of the first point
     * @param  float $lon1 Longitude of the first point
     * @param  float $lat2 Latitude of the second point
     * @param  float $lon2 Longitude of the second point
     * @return array       Array of values in many distance units
     */
    public function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('miles', 'feet', 'yards', 'kilometers', 'meters');
    }
}
