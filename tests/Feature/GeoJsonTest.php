<?php

namespace Tests\Feature;

use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GeoJsonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function testGeoJsonIsGeneratedCorrectly(): void
    {
        $reference = Reference::factory()->active()->create();
        $response = $this->getJson('/api/geojson');

        $response
            ->assertStatus(200)
            ->assertJsonPath('features.0.type', 'Feature')
            ->assertJsonPath('features.0.geometry.type', 'Point')
            ->assertJsonPath('features.0.geometry.coordinates', [
                $reference->location->getLongitude(),
                $reference->location->getLatitude(),
            ])
            ->assertJsonPath('features.0.properties.reference', $reference->reference)
            ->assertJsonPath('features.0.properties.is_activated', true)
            ->assertJsonPath('features.0.properties.first_activation_date', $reference->first_activation_date)
            ->assertJsonPath('features.0.properties.latest_activation_date', $reference->latest_activation_date)
            ->assertJsonPath('features.0.properties.name', $reference->name)
            ->assertJsonPath('features.0.properties.wdpa_id', $reference->wdpa_id)
            ->assertJsonPath('features.0.properties.natura_2000_area', $reference->natura_2000_area);
    }

    /**
     * Test getting correct icon for non-approved reference
     */
    public function testGettingIconForNonApprovedReference(): void
    {
        // New non-approved reference should be purple
        $reference = Reference::factory()->create(['approval_status' => 'received']);
        $response = $this->getJson('/api/geojson');
        $response
            ->assertStatus(200)
            ->assertJsonPath('features.0.properties.icon', 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/purple.png');
    }

    /**
     * Test getting correct icon for non-activated reference
     */
    public function testGettingIconForNonActivatedReference(): void
    {
        // Non-activated reference should return tree
        $reference = Reference::factory()->create(['approval_status' => 'saved', 'latest_activation_date' => null]);
        $response = $this->getJson('/api/geojson');
        $response
            ->assertStatus(200)
            ->assertJsonPath('features.0.properties.icon', 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/tree.png');
    }

    /**
     * Test getting correct icons for references activated in the past
     */
    public function testGettingIconForReferenceActivatedLongTimeAgo(): void
    {
        $reference = Reference::factory()->create(['approval_status' => 'saved', 'latest_activation_date' => fake()->dateTimeBetween('-10 year', '-5 year')]);
        $response = $this->getJson('/api/geojson');
        $response
            ->assertStatus(200)
            ->assertJsonPath('features.0.properties.icon', 'https://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png');
    }

    /**
     * Test that references can be filtered by name
     */
    public function testReferencesCanBeFilteredByName(): void
    {
        Reference::factory()->create(['reference' => 'OHFF-0001']);
        Reference::factory()->create(['reference' => 'OHFF-0002']);

        $response = $this->getJson('/api/geojson?filter[reference]=OHFF-0001');

        $response
            ->assertJson(fn (AssertableJson $json) => $json->has('type')
                ->has('features', 1, fn (AssertableJson $json) => $json->where('type', 'Feature')
                    ->where('properties.reference', 'OHFF-0001')
                    ->etc()));
    }

    /**
     * Test that references can be filtered by approval status
     */
    public function testReferencesCanBeFilteredByApprovalStatus(): void
    {
        Reference::factory()->create(['reference' => 'OHFF-0001', 'approval_status' => 'saved']);
        Reference::factory()->create(['reference' => 'OHFF-0002', 'approval_status' => 'received']);

        $response = $this->getJson('/api/geojson?filter[approval_status]=received');

        $response
            ->assertJson(fn (AssertableJson $json) => $json->has('type')
                ->has('features', 1, fn (AssertableJson $json) => $json->where('type', 'Feature')
                    ->where('properties.reference', 'OHFF-0002')
                    ->etc()));
    }

    /**
     * Test that references can be filtered by if they are activated or not
     */
    public function testReferencesCanBeFilteredByActivationStatus(): void
    {
        Reference::factory()->create(['reference' => 'OHFF-0001', 'first_activation_date' => null, 'latest_activation_date' => null]);
        Reference::factory()->create(['reference' => 'OHFF-0002']);

        $response = $this->getJson('/api/geojson?filter[not_activated]=true');

        $response
            ->assertJson(fn (AssertableJson $json) => $json->has('type')
                ->has('features', 1, fn (AssertableJson $json) => $json->where('type', 'Feature')
                    ->where('properties.reference', 'OHFF-0001')
                    ->etc()));

        $response = $this->getJson('/api/geojson?filter[activated]=true');

        $response
            ->assertJson(fn (AssertableJson $json) => $json->has('type')
                ->has('features', 1, fn (AssertableJson $json) => $json->where('type', 'Feature')
                    ->where('properties.reference', 'OHFF-0002')
                    ->etc()));
    }
}
