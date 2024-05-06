<?php

namespace Tests\Feature;

use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPCoord\Point\ProjectedPoint;
use Tests\TestCase;

class CacheWarmupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that coordinate conversion is cached
     */
    public function testCoordinateConversionIsCached(): void
    {
        $reference = Reference::factory()->activatedRecently()->create();
        Artisan::call('app:warmup-cache');

        $this->assertTrue(Cache::has('etrs98-'.$reference->id));
        $this->assertInstanceOf(ProjectedPoint::class, Cache::get('etrs98-'.$reference->id));
    }
}
