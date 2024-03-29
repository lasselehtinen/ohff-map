<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class CacheWarmup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warmup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warmup cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Clear the response cache
        Artisan::call('responsecache:clear');

        // Warm up cache
        $urls = [
            url('/geojson'),
            url('/geojson?filter%5Bactivated_by%5D=&filter%5Bnot_activated_by%5D=&filter%5Breference%5D=&filter%5Bapproval_status%5D='),
            url('/geojson?filter%5Bactivated_by%5D=&filter%5Bnot_activated_by%5D=&filter%5Breference%5D=&filter%5Bnot_activated%5D=on&filter%5Bapproval_status%5D='),
            url('/geojson?filter%5Bactivated_by%5D=&filter%5Bnot_activated_by%5D=&filter%5Breference%5D=&filter%5Bactivited_this_year%5D=on&filter%5Bapproval_status%5D='),
            url('/geojson?filter%5Bactivated_by%5D=&filter%5Bnot_activated_by%5D=&filter%5Breference%5D=&filter%5Bapproval_status%5D=received'),
            url('/geojson?filter%5Bactivated_by%5D=&filter%5Bnot_activated_by%5D=&filter%5Breference%5D=&filter%5Bapproval_status%5D=approved'),
            url('/geojson?filter%5Bactivated_by%5D=&filter%5Bnot_activated_by%5D=&filter%5Breference%5D=&filter%5Bapproval_status%5D=saved'),
        ];

        if (App::environment('production')) {
            foreach ($urls as $url) {
                Http::timeout(600)->get($url);
            }
        }

        return 0;
    }
}
