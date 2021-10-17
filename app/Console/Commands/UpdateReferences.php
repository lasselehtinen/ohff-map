<?php

namespace App\Console\Commands;

use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
                return ($value === "" || $value === '-') ? null : $value;
            }, $sourceReference);

            $reference = Reference::firstOrCreate(['reference' => $sourceReference['reference']], [
                'name' => $sourceReference['name'],
                'status' => $sourceReference['status'],
                'iota_reference' => $sourceReference['iota'],
            ]);

            // Update location
            $reference->location = new Point($sourceReference['latitude'], $sourceReference['longitude']);

            // Add relations
            $program = Program::where('name', $sourceReference['program'])->firstOrFail();
            $reference->program()->associate($program);

            $dxcc = Dxcc::where('name', $sourceReference['dxcc'])->first();
            $reference->dxcc()->associate($dxcc);

            $continent = Continent::where('name', $sourceReference['continent'])->first();
            $reference->continent()->associate($continent);

            $reference->save();

            $bar->advance();
        });

        $bar->finish();

        return 0;
    }
}
