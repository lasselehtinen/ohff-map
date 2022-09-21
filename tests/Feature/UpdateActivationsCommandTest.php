<?php

namespace Tests\Feature;

use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateActivationsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testActivationIsParsedCorrectlyFromWwffSite()
    {
        // Create one known reference
        $reference = Reference::factory(['reference' => 'OHFF-1079'])
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();

        Artisan::call('update:activations');

        // OH2BAV should have three activations
        $user = User::where('callsign', 'OH2BAV')->with('activations')->first();
        $this->assertSame(4, $user->activations->where('reference', 'OHFF-1079')->count());
        $firstActivation = $user->activations->where('reference', 'OHFF-1079')->sortBy('pivot.activation_date')->first();
        $latestActivation = $user->activations->where('reference', 'OHFF-1079')->sortByDesc('pivot.activation_date')->first();
        $this->assertSame('2021-03-07', date('Y-m-d', strtotime($firstActivation->pivot->activation_date)));
        $this->assertSame('2022-08-14', date('Y-m-d', strtotime($latestActivation->pivot->activation_date)));

        // OH2NOS should have one activation
        $user = User::where('callsign', 'OH2NOS')->with('activations')->first();
        $this->assertSame(1, $user->activations->where('reference', 'OHFF-1079')->count());
    }
}
