<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Grimzy\LaravelMysqlSpatial\Types\MultiPolygon;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Console\Command;

class CheckCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:coordinates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that coordinates are inside Protected Planet area/polygon';

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
        // We can only check references which have Protected Planet area/polygon
        $references = Reference::whereNotNull('area')->get();

        foreach ($references as $reference) {
            if ($reference->area instanceof Polygon || $reference->area instanceof MultiPolygon) {
                $results = Reference::within('location', $reference->area)->get();

                if ($results->count() <> 1) {
                    $this->info($reference->reference . ' ' . $results->count() . ' ' . implode(',', $results->pluck('reference')->toArray()));
                }
            }
        }

        return 0;
    }
}
