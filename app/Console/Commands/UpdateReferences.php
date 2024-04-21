<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class UpdateReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-references';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetched the CSV containing the WWFF references and updates them to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Download CSV and filter out other than OHFF
        $reader = Reader::createFromString(Http::get('http://wwff.co/wwff-data/wwff_directory.csv'));
        $reader->setHeaderOffset(0);
        $records = Statement::create()
            ->where(fn (array $reference): bool => $reference['program'] === 'OHFF')
            ->process($reader);
        $references = collect($records->getRecords());

        /*$references = $references->filter(function ($reference, $key) {
            return $reference['program'] === 'OHFF';
        });*/

        // Create progress bar
        $bar = $this->output->createProgressBar($references->count());
        $bar->setFormat('very_verbose');
        $bar->start();

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
            $reference = Reference::firstOrCreate(['reference' => $sourceReference['reference']]);

            $reference->fill([
                'name' => $sourceReference['name'],
                'status' => $sourceReference['status'],
                'latitude' => $sourceReference['latitude'],
                'longitude' => $sourceReference['longitude'],
                'iota_reference' => $sourceReference['iota'],
                'wdpa_id' => $protectedPlanetId,
                'valid_from' => ($sourceReference['validFrom'] === '0000-00-00') ? null : $sourceReference['validFrom'],
                'latest_activation_date' => $sourceReference['lastAct'],
            ]);

            // Check if new reference has been approved or already active/deleted
            if ($sourceReference['status'] !== 'proposed') {
                $reference->approval_status = 'saved'; /** @phpstan-ignore-line */
            }

            $reference->save();
            $bar->advance();
        });

        $bar->finish();
    }

    public function filterOnlyOhff($row)
    {
        dd($row);
    }
}
