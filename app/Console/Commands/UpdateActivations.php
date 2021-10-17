<?php

namespace App\Console\Commands;

use App\Models\Reference;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;

class UpdateActivations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:activations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapes the activations for each reference';

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
        $references = Reference::all();

        // Create progress bar
        $bar = $this->output->createProgressBar($references->count());
        $bar->setFormat('very_verbose');
        $bar->start();

        $references->each(function ($reference, $key) use ($bar) {
            $response = Http::get('https://wwff.co/directory/?showRef=' . $reference['reference']);
            $crawler = new Crawler($response->body());

            $table = $crawler->filterXPath('//*[@id="logsearch"]/form/table')->filter('tr')->each(function ($tr, $i) {
                return $tr->filter('td')->each(function ($td, $i) {
                    return trim($td->text());
                });
            });
            
            // Get first and latest activation date
            $dates = collect($table)->filter(function ($row, $key) {
                $firstColumnIsDate = (array_key_exists('0', $row) && DateTime::createFromFormat('Y-m-d', $row[0]) !== false);
                $secondColumnIsDate = (array_key_exists('1', $row) && DateTime::createFromFormat('Y-m-d', $row[1]) !== false);
                $hasTotalQsoCount = (array_key_exists('2', $row) && ctype_digit($row[2]));
                $hasTotalActivatorCount = (array_key_exists('3', $row) && ctype_digit($row[3]));

                return $firstColumnIsDate && $secondColumnIsDate && $hasTotalQsoCount && $hasTotalActivatorCount;
            })->first();

            if (!is_null($dates)) {
                $reference->first_activation_date = $dates[0];
                $reference->latest_activation_date = $dates[1];
                $reference->save();
            }

            // Clean up all other than those that look like activations
            $activations = collect($table)->filter(function ($activation, $key) {
                $firstColumnIsDate = (array_key_exists('0', $activation) && DateTime::createFromFormat('Y-m-d', $activation[0]) !== false);
                $secondColumnIsNotDate = (array_key_exists('1', $activation) && DateTime::createFromFormat('Y-m-d', $activation[1]) === false);
                $hasQsoCount = (array_key_exists('2', $activation) && ctype_digit($activation[2]));
                $hasActivatorCount = (array_key_exists('3', $activation) && ctype_digit($activation[3]));

                return $firstColumnIsDate && $secondColumnIsNotDate && $hasQsoCount && $hasActivatorCount;
            });

            // Remap and remove the (op: OHXXXX) from callsigns
            $activations = $activations->map(function ($activation, $key) {
                $activationDate = DateTime::createFromFormat('Y-m-d', strtok($activation[0], ' '));

                return [
                    'date' => ($activationDate !== false) ? $activationDate : null,
                    'callsign' => strtok($activation[1], ' '),
                ];
            });

            // Add user activations to reference
            $activations->each(function ($activation, $key) use ($reference, $bar) {
                // Create / fetch user exists
                $user = User::firstOrCreate(['callsign' => strtok($activation['callsign'], '/')], ['password' => Hash::make(Str::random(8))]);
                
                // Prevent duplicates
                $activationExists = $user->with('activations')->whereHas('activations', function($query) use ($reference, $activation) {
                    $query->where('reference_id', $reference->id)->where('activation_date', $activation['date']);
                })->exists();

                if ($activationExists === false) {
                    $user->activations()->attach($reference, ['activation_date' => $activation['date']]);
                }
            });

            // Wait a bit not to hammer the WWFF site
            //sleep(5);

            $bar->advance();
        });

        $bar->finish();

        return 0;
    }
}
