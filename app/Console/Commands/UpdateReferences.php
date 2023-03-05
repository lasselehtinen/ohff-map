<?php

namespace App\Console\Commands;

use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\Csv\Reader;

class UpdateReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:references';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetched the CSV containing the WWFF references and updates them to database';

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
        // Download CSV
        $csv = Http::get('http://wwff.co/wwff-data/wwff_directory.csv');
        $reader = Reader::createFromString($csv);
        $reader->setHeaderOffset(0);
        $references = collect($reader->getRecords());

        // Filter out other than OHFF
        $references = $references->filter(function ($reference, $key) {
            return $reference['program'] === 'OHFF';
        });

        // Create progress bar
        $bar = $this->output->createProgressBar($references->count());
        $bar->setFormat('very_verbose');
        $bar->start();

        // Create / update programs
        $references->pluck('program')->unique()->each(function ($program) {
            Program::firstOrCreate(['name' => $program]);
        });

        // Create / update dxcc's
        $references->pluck('dxcc')->unique()->each(function ($dxcc) {
            Dxcc::firstOrCreate(['name' => $dxcc]);
        });

        // Create / update continents
        $references->pluck('continent')->unique()->each(function ($continent) {
            Continent::firstOrCreate(['name' => $continent]);
        });

        // Create / update references
        $references->each(function ($sourceReference) use ($bar) {
            // Replace empty and '-' values with null
            $sourceReference = array_map(function ($value) {
                return ($value === '' || $value === '-') ? null : $value;
            }, $sourceReference);

            // Check if we can parse Protected Planet ID from website
            $protectedPlanetId = null;

            if (Str::contains($sourceReference['website'], 'protectedplanet')) {
                $chunks = explode('/', $sourceReference['website']);
                $lastPart = end($chunks);
                $protectedPlanetId = (is_numeric($lastPart)) ? $lastPart : null;
            }

            // Update attributes
            $reference = Reference::where('reference', $sourceReference['reference'])->first();

            if (is_null($reference)) {
                $reference = Reference::create(['reference' => $sourceReference['reference']]);
            }

            $reference->update([
                'name' => $sourceReference['name'],
                'status' => $sourceReference['status'],
                'iota_reference' => $sourceReference['iota'],
                'wdpa_id' => $protectedPlanetId,
                'latest_activation_date' => $sourceReference['lastAct'],
            ]);

            // Set or update location if changed. This is so that the model does not appear dirty unnecessarily.
            if (is_null($reference->location) || ($reference->location->getLat() !== floatval($sourceReference['latitude']) || $reference->location->getLng() !== floatval($sourceReference['longitude']))) { /** @phpstan-ignore-line */
                $reference->location = new Point($sourceReference['latitude'], $sourceReference['longitude']); /** @phpstan-ignore-line */
            }

            // Add relations
            $program = Program::where('name', $sourceReference['program'])->firstOrFail();
            $reference->program()->associate($program); /** @phpstan-ignore-line */
            $dxcc = Dxcc::where('name', $sourceReference['dxcc'])->first();
            $reference->dxcc()->associate($dxcc); /** @phpstan-ignore-line */
            $continent = Continent::where('name', $sourceReference['continent'])->first();
            $reference->continent()->associate($continent); /** @phpstan-ignore-line */

            // Check if new reference has been approved
            if ($sourceReference['status'] === 'active' && $reference->approval_status === 'approved') { /** @phpstan-ignore-line */
                $reference->approval_status = 'saved';
            }

            $reference->save();
            $bar->advance();
        });

        $bar->finish();

        // Clear and warmup the cache
        Artisan::call('cache:warmup');

        return 0;
    }
}
