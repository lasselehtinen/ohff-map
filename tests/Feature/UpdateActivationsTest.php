<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateActivationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that activation is parsed correctly
     *
     * @return void
     */
    public function testActivationIsParsedCorrectlyFromWwffSite()
    {
        // Create one known reference
        $reference = Reference::factory(['reference' => 'OHFF-1079'])->activatedRecently()->create();

        Artisan::call('app:update-activations');

        // OH2BAV should have four activations
        $operator = Operator::where('callsign', 'OH2BAV')->first();
        $this->assertSame(4, $operator->activations->where('reference', 'OHFF-1079')->count());
        $firstActivation = $operator->activations->where('reference', 'OHFF-1079')->sortBy('pivot.activation_date')->first();
        $latestActivation = $operator->activations->where('reference', 'OHFF-1079')->sortByDesc('pivot.activation_date')->first();
        $this->assertSame('2021-03-07', date('Y-m-d', strtotime($firstActivation->pivot->activation_date)));
        $this->assertSame(72, $firstActivation->pivot->qso_count);
        $this->assertSame(67, $firstActivation->pivot->chaser_count);

        $this->assertSame('2022-08-14', date('Y-m-d', strtotime($latestActivation->pivot->activation_date)));

        // OH2NOS should have one activation
        $operator = Operator::where('callsign', 'OH2NOS')->with('activations')->first();
        $this->assertSame(1, $operator->activations->where('reference', 'OHFF-1079')->count());
    }

    /**
     * Test that activation with more complex callsign is parsed correctly
     *
     * @return void
     */
    public function testGettingActivationsWithSpecialCallSign()
    {
        // Create one known reference
        $reference = Reference::factory(['reference' => 'OHFF-0112'])->activatedRecently()->create();

        Artisan::call('app:update-activations');

        //  OH/LA1TPA/P should have one activation
        $operator = Operator::where('callsign', 'LA1TPA')->with('activations')->first();
        $this->assertSame(1, $operator->activations->where('reference', 'OHFF-0112')->count());
    }
}
