<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;

class ListNonApprovedReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:non_approved';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists non approved references';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $references = Reference::where('approval_status', 'received')->orderBy('reference')->get()->map(function ($reference, $key) {
            return [
                'reference' => $reference->reference,
                'name' => $reference->name,
                'latitude' => $reference->location->getLat(),
                'longitude' => $reference->location->getLng(),
                'link' => 'https://www.protectedplanet.net/'.$reference->wdpa_id,
            ];
        });

        $this->table(
            ['Reference', 'Name', 'Latitude', 'Longitude', 'Link'],
            $references->toArray()
        );

        return 0;
    }
}
