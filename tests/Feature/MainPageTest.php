<?php

namespace Tests\Feature;

use App\Http\Controllers\GeoJsonController;
use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

class MainPageTest extends TestCase
{
    use RefreshDatabase;

    public function testMainPageIsPublicAndUsesOpenstreetmapAssets()
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('/vendor/leaflet/leaflet.js')
            ->assertSee('/js/index.js?v='.filemtime(public_path('js/index.js')), false)
            ->assertSee('/css/style.css?v='.filemtime(public_path('css/style.css')), false)
            ->assertSee('id="map"', false)
            ->assertDontSee('google', false);

        $this->assertFileExists(public_path('vendor/leaflet/leaflet.js'));
        $this->assertStringContainsString('tile.openstreetmap.org', (string) file_get_contents(public_path('js/index.js')));
    }

    public function testFiltersAreKept()
    {
        $response = $this->get('/?filter[activated_by]=oh0aa&filter[reference]=OHFF-0001');

        $response->assertOk()
            ->assertSee('name="filter[activated_by]"', false)
            ->assertSee('name="filter[not_activated_by]"', false)
            ->assertSee('name="filter[reference]"', false)
            ->assertSee('name="filter[not_activated]"', false)
            ->assertSee('name="filter[activited_this_year]"', false)
            ->assertSee('name="filter[approval_status]"', false)
            ->assertSee('value="OH0AA"', false)
            ->assertSee('value="OHFF-0001"', false);
    }

    public function testSelectedBooleansAndApprovalStatusAreRestored()
    {
        $this->get('/?filter[not_activated]=on&filter[approval_status]=approved')
            ->assertOk()
            ->assertSee('name="filter[not_activated]" checked', false)
            ->assertDontSee('name="filter[activited_this_year]" checked', false)
            ->assertSee('value="approved" selected', false)
            ->assertDontSee('value="saved" selected', false);

        $this->get('/?filter[activited_this_year]=on&filter[approval_status]=saved')
            ->assertOk()
            ->assertDontSee('name="filter[not_activated]" checked', false)
            ->assertSee('name="filter[activited_this_year]" checked', false)
            ->assertSee('value="saved" selected', false);
    }

    public function testNothingIsPreselectedWithoutFilters()
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee(' checked', false)
            ->assertDontSee(' selected', false);
    }

    public function testGeojsonIsPublicAndSkipsReferencesWithoutLocation()
    {
        Reference::factory(['status' => 'active'])
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();

        $this->get('/geojson')
            ->assertOk()
            ->assertJson(['type' => 'FeatureCollection', 'features' => []]);
    }

    public function testActivationScopesUseTheDateFilledByTheDirectoryImport()
    {
        $factory = Reference::factory()->for(Program::factory())->for(Dxcc::factory())->for(Continent::factory());

        // update:references sets only the latest date, update:activations sets the first one later
        $imported = $factory->create(['first_activation_date' => null, 'latest_activation_date' => '2024-05-01']);
        $unactivated = $factory->create(['first_activation_date' => null, 'latest_activation_date' => null]);

        $this->assertEquals([$unactivated->id], Reference::notActivated()->pluck('id')->all());
        $this->assertEquals([$imported->id], Reference::activated()->pluck('id')->all());
    }

    public function testLatestActivatorsPicksTheNewestActivationOfEachReference()
    {
        $factory = Reference::factory()->for(Program::factory())->for(Dxcc::factory())->for(Continent::factory());
        $first = $factory->create();
        $second = $factory->create();
        $untouched = $factory->create();
        $older = User::factory()->create(['callsign' => 'OH0OLD']);
        $newer = User::factory()->create(['callsign' => 'OH0NEW']);

        foreach ([[$newer, $first, '2024-05-01'], [$older, $first, '2020-05-01'], [$older, $second, '2021-01-01'], [$newer, $second, null]] as [$user, $reference, $date]) {
            DB::table('user_activations')->insert(['user_id' => $user->id, 'reference_id' => $reference->id, 'activation_date' => $date]);
        }

        $activators = Reference::latestActivators(collect([$first->id, $second->id, $untouched->id]));

        $this->assertSame('OH0NEW', $activators[$first->id]);
        $this->assertSame('OH0OLD', $activators[$second->id]);
        $this->assertArrayNotHasKey($untouched->id, $activators);
    }

    public function testCoordinatesAreConvertedAndCachedAsPlainValues()
    {
        $reference = new Reference;
        $reference->id = 123;
        $reference->location = new Point(60.1699, 24.9384);

        $coordinates = $reference->getETRS89Coordinates();

        $this->assertEqualsWithDelta(6672117.81, (float) $coordinates['northing'], 0.01);
        $this->assertEqualsWithDelta(385610.58, (float) $coordinates['easting'], 0.01);
        $this->assertSame($coordinates, Cache::get('etrs89-coordinates-123'));
        $this->assertIsString($coordinates['northing']);
    }

    public function testReferenceWithoutLocationHasNoCoordinates()
    {
        $this->assertNull((new Reference)->getETRS89Coordinates());
    }

    public function testMarkerIconsAreServedLocally()
    {
        $controller = new GeoJsonController;

        $cases = [
            [['approval_status' => 'received'], 'purple'],
            [['approval_status' => 'saved', 'latest_activation_date' => null], 'tree'],
            [['approval_status' => 'saved', 'latest_activation_date' => now()->toDateString()], 'blue'],
            [['approval_status' => 'saved', 'latest_activation_date' => now()->subYears(2)->toDateString()], 'yellow'],
            [['approval_status' => 'saved', 'latest_activation_date' => now()->subYears(9)->toDateString()], 'red'],
        ];

        foreach ($cases as [$attributes, $color]) {
            $icon = $controller->getIcon(new Reference($attributes));

            $this->assertSame('/images/markers/'.$color.'.svg', $icon);
            $this->assertFileExists(public_path(ltrim($icon, '/')));
        }
    }
}
