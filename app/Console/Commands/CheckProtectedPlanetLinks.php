<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckProtectedPlanetLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:protected_planet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the Protected Planet links';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $references = Reference::whereNotNull('wdpa_id')->get();

        // Create progress bar
        $bar = $this->output->createProgressBar($references->count());
        $bar->setFormat('very_verbose');
        $bar->start();

        foreach ($references as $reference) {
            $response = Http::get('https://www.protectedplanet.net/'.$reference->wdpa_id); /** @phpstan-ignore-line */
            if ($response->status() === 500) {
                $this->info($reference->reference); /** @phpstan-ignore-line */
            }

            sleep(3);
            $bar->advance();
        }

        $bar->finish();

        return 0;
    }
}
