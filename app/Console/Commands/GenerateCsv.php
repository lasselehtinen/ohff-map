<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class GenerateCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates static CSV files for activations etc.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertOne(['Reference', 'Callsign', 'Activation date', 'QSO count', 'Chaser count']);

        foreach (User::cursor() as $user) {
            foreach ($user->activations as $activation) {
                $csv->insertOne([
                    $activation->reference,
                    $user->callsign,
                    Carbon::parse($activation->pivot->activation_date)->format('Y-m-d'), // @phpstan-ignore-line
                    $activation->pivot->qso_count, // @phpstan-ignore-line
                    $activation->pivot->chaser_count, // @phpstan-ignore-line
                ]);
            }
        }

        Storage::disk('public')->put('csv/activations.csv', $csv->getContent());

        return Command::SUCCESS;
    }
}
