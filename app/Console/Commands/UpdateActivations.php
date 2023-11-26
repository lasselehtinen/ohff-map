<?php

namespace App\Console\Commands;

use App\Models\Reference;
use App\Models\User;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class UpdateActivations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:activations {--all}';

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
        // Get references that are activated in the past two months
        if ($this->option('all')) {
            $references = Reference::all();
        } else {
            $references = Reference::where('latest_activation_date', '>=', now()->subDays(60)->startOfDay()->toDateTimeString())->get();
        }

        // Create progress bar
        $bar = $this->output->createProgressBar($references->count());
        $bar->setFormat('very_verbose');
        $bar->start();

        // Update activation dates and activations
        $references->each(function ($reference, $key) use ($bar) {
            $response = Http::get('https://wwff.co/directory/?showRef='.$reference['reference']);
            $crawler = new Crawler($response->body());

            $table = $crawler->filterXPath('//*[@id="logsearch"]/form/table')->filter('tr')->each(function ($tr, $i) {
                return $tr->filter('td')->each(function ($td, $i) {
                    return trim($td->text());
                });
            });

            // Get first and latest activation dates
            $dates = $this->getActivationDates($table);

            if (is_array($dates)) {
                $reference->first_activation_date = $dates[0]; /** @phpstan-ignore-line */
                $reference->latest_activation_date = $dates[1]; /** @phpstan-ignore-line */
                $reference->save();
            }

            $activations = $this->getActivations($table);

            // Add user activations to reference
            $activations->each(function ($activation, $key) use ($reference) {
                // Create / fetch user exists
                $user = User::firstOrCreate(['callsign' => $activation['callsign']], ['password' => Hash::make(Str::random(8))]);

                // Prevent duplicates
                $activationsOnReference = $user->activations()->get()->filter(function ($activationReference, $key) use ($reference, $activation) {
                    /** @phpstan-ignore-next-line */
                    return $activationReference->id === $reference->id && $activationReference->pivot->activation_date === $activation['date']->format('Y-m-d');
                });

                if ($activationsOnReference->count() === 0) {
                    $user->activations()->attach($reference, [
                        'activation_date' => $activation['date'],
                        'qso_count' => $activation['qso_count'],
                        'chaser_count' => $activation['chaser_count'],
                    ]);
                }
            });

            $bar->advance();
        });

        $bar->finish();

        // Clear the response cache
        Artisan::call('cache:warmup');

        return 0;
    }

    /**
     * Get the first and latest activation date
     *
     * @return array|null
     */
    public function getActivationDates($table)
    {
        $dates = collect($table)->filter(function ($row, $key) {
            // Check that the row has four fields. First two should be dates and two after that should be digits
            return count($row) === 4 && DateTime::createFromFormat('Y-m-d', $row[0]) !== false && DateTime::createFromFormat('Y-m-d', $row[1]) !== false && is_numeric($row[2]) && is_numeric($row[3]);
        })->transform(function ($dates, $key) {
            return array_slice($dates, 0, 2);
        });

        return $dates->first();
    }

    /**
     * Get the activations
     *
     * @return Collection
     */
    public function getActivations($table)
    {
        // Clean up all other than those that look like activations
        $activations = collect($table)->filter(function ($row, $key) {
            // Check that the row has four fields. First one should be dates, second callsign and two after that should be digits
            return count($row) === 4 && DateTime::createFromFormat('Y-m-d', $row[0]) !== false && DateTime::createFromFormat('Y-m-d', $row[1]) === false && is_numeric($row[2]) && is_numeric($row[3]);
        })->map(function ($activation, $key) {
            // Remap and remove the (op: OHXXXX) from callsigns
            $activationDate = DateTime::createFromFormat('Y-m-d', strtok($activation[0], ' '));

            return [
                'date' => ($activationDate !== false) ? $activationDate : null,
                'callsign' => strtok($activation[1], ' '),
                'qso_count' => intval($activation[2]),
                'chaser_count' => intval($activation[3]),
            ];
        });

        return $activations;
    }
}
