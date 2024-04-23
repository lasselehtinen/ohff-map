<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateReferencesTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('app:update-references');
    }

    /**
     * Test that reference is updated to the database
     */
    public function testUpdateReferencesCommandUpdatesDatabase(): void
    {
        $this->assertDatabaseHas('references', [
            'reference' => 'OHFF-0001',
            'name' => 'Itäisen Suomenlahden kansallispuisto',
            'status' => 'active',
            'latitude' => 60.30706,
            'longitude' => 27.16578,
            'iota_reference' => null,
            'wdpa_id' => 7500,
            'valid_from' => null,
        ]);
    }

    /**
     * Test that other than OHFF references are not updated
     */
    public function testUpdateReferencesCommandDoesNotIncludeOtherThanOhffReferences(): void
    {
        $this->assertDatabaseMissing('references', [
            'reference' => '3CFF-0011',
        ]);
    }
}
