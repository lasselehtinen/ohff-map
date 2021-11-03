<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Grimzy\LaravelMysqlSpatial\Types\Geometry;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Grimzy\LaravelMysqlSpatial\Types\MultiPolygon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;

class ParseProtectedPlanetShapeFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:protected_planet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parses the ShapeFile from Protected Planet to add coordinates';

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
        // Create progress bar
        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat('very_verbose');
        $bar->start();

        foreach ($shapeFiles as $shapeFile) {
            // Open Shapefile
            $shapeFile = new ShapefileReader(Storage::disk('resources')->path($shapeFile));
            
            // Read all the records
            while ($geometry = $shapeFile->fetchRecord()) {
                $shapeData = $geometry->getDataArray();

                // Search if we have reference with that World Database on Protected Areas ID
                $reference = Reference::where('wdpa_id', $shapeData['WDPA_PID'])->first();
                
                if (!is_null($reference)) {
                    $geometryData = $geometry->getArray();
                    $area = Geometry::fromJson($geometry->getGeoJSON());
                    
                    if ($area instanceof Polygon || $area instanceof MultiPolygon) {
                        $reference->area = $area;
                        $reference->save();
                        $this->info('Updated ' . $reference->reference);
                    }
                }

                $bar->advance();
            }
        }

        $bar->finish();
        
        return Command::SUCCESS;
    }
}
