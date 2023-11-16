<?php

namespace Tests\Feature;

use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateCsvTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUserActivationsCsvIsGeneratedCorrectly()
    {
        // Create one known reference
        $reference = Reference::factory(['reference' => 'OHFF-1079', 'latest_activation_date' => now()])
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();

        Artisan::call('update:activations');

        // OH2BAV should have four activations
        $user = User::where('callsign', 'OH2BAV/P')->with('activations')->first();
        $this->assertSame(4, $user->activations->where('reference', 'OHFF-1079')->count());

        // Generate and validate CSV
        Storage::fake('public');
        Artisan::call('generate:csv');

        Storage::disk('public')->assertExists('csv/activations.csv');

        $contents = Storage::disk('public')->get('csv/activations.csv');
        $this->assertStringContainsString('Reference;Callsign;"Activation date";"QSO count";"Chaser count"', $contents);
        $this->assertStringContainsString('OHFF-1079;OH2BAV/P;2022-08-14;93;82', $contents);
    }
}
