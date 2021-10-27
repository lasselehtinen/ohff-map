<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class UpdateCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:coordinates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates coordinates based on OH3BHL list';

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
        $path = resource_path('oh3bhl-coordinates.csv');

        if (($handle = fopen($path, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $reference = Reference::where('reference', $data[0])->first();
                if (array_key_exists('1', $data) && array_key_exists('2', $data)) {
                    $reference->location = new Point($data[1], $data[2]);
                    $reference->save();
                }
            }
            fclose($handle);
        }

        return Command::SUCCESS;
    }
}
