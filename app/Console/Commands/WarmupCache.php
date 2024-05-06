<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;

use function Laravel\Prompts\progress;

class WarmupCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:warmup-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warms up the application cache, like coordinate conversions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = progress(
            label: 'Updating ETRS89 coordinates to cache',
            steps: Reference::all(),
            callback: function ($reference, $progress) {
                $progress->label("Updating {$reference->reference}");

                return $reference->getETRS89Coordinates();
            },
            hint: 'This may take some time.',
        );
    }
}
