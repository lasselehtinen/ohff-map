<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;

class CheckReferenceNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:reference_names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get list of all Shapefiles that contain polygons
        $shapeFiles = collect(Storage::disk('resources')->allFiles())->filter(function ($filename, $key) {
            return Str::contains($filename, 'polygons') && Str::endsWith($filename, '.shp');
        });

        // Get total count
        $totalCount = $shapeFiles->sum(function ($shapeFile) {
            $shapeFile = new ShapefileReader(Storage::disk('resources')->path($shapeFile));
            return $shapeFile->getTotRecords();
        });

        // Create progress bar
        /*
        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat('very_verbose');
        $bar->start();
        */
        foreach ($shapeFiles as $shapeFile) {
            // Open Shapefile
            $shapeFile = new ShapefileReader(Storage::disk('resources')->path($shapeFile));
            
            // Read all the records
            while ($geometry = $shapeFile->fetchRecord()) {
                $shapeData = $geometry->getDataArray();

                // Search if we have reference with that World Database on Protected Areas ID
                $reference = Reference::where('wdpa_id', $shapeData['WDPA_PID'])->first();
                
                if (!is_null($reference)) {
                    $this->info($reference->reference.';'.$reference->name.';'.$shapeData['NAME'].';'.$shapeData['ORIG_NAME'].';'.levenshtein($shapeData['NAME'], $reference->name));
                }

                //$bar->advance();
            }
        }

        //$bar->finish();
        
        return 0;
    }
}
