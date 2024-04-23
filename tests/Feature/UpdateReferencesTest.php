<?php

namespace Tests\Feature;

use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateReferencesTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Http::fake([
            'http://wwff.co/wwff-data/wwff_directory.csv' => Http::response(file_get_contents(base_path('tests/Sample files/wwff_directory.csv'))),
        ]);

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
            'iota_reference' => null,
            'wdpa_id' => 7500,
            'valid_from' => null,
        ]);

        // Validate coordinates separately
        $reference = Reference::where('reference', 'OHFF-0001')->firstOrFail();
        $this->assertSame(60.30706, $reference->location->getLatitude());
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
