<?php

namespace Tests\Feature;

use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use App\Models\User;
use App\Services\ActivityStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function testPageIsPublicWithCallsignsButNoPrivateDetails()
    {
        $user = User::factory()->create(['callsign' => 'OH0AAA', 'name' => 'Private Person', 'email' => 'private@example.com']);
        $this->activate($user, $this->reference(), now()->toDateString(), 12, 5);

        $this->get('/statistics')
            ->assertOk()
            ->assertSee('OH0AAA')
            ->assertDontSee('Private Person')
            ->assertDontSee('private@example.com');
    }

    public function testPageWorksWithoutData()
    {
        $this->get('/statistics')
            ->assertOk()
            ->assertSee('No activation logs')
            ->assertSee('No county is that close');
    }

    public function testOtherMetricsAreNotShown()
    {
        $this->get('/statistics')
            ->assertDontSee('Never activated')
            ->assertDontSee('Coverage')
            ->assertDontSee('awaiting review');
    }

    public function testActiveAndNewActivators()
    {
        $reference = $this->reference();
        [$veteran, $newcomer, $lapsed] = User::factory()->count(3)->create();

        $this->activate($veteran, $reference, now()->subYears(3)->toDateString());
        $this->activate($veteran, $reference, now()->subMonth()->toDateString());
        $this->activate($newcomer, $reference, now()->subMonths(2)->toDateString());
        $this->activate($lapsed, $reference, now()->subYears(2)->toDateString());

        $summary = app(ActivityStatistics::class)->summary();

        $this->assertSame(2, $summary['active_activators']);
        $this->assertSame(1, $summary['new_activators']);
    }

    public function testActivationsAreComparedToTheYearBefore()
    {
        $first = $this->reference();
        $second = $this->reference();
        [$user, $operator] = User::factory()->count(2)->create();
        $date = now()->startOfMonth()->toDateString();

        // Two operators on the same reference and date are one activation
        $this->activate($user, $first, $date);
        $this->activate($operator, $first, $date);
        $this->activate($user, $second, $date);
        $this->activate($user, $first, now()->startOfMonth()->subYear()->toDateString());

        $statistics = app(ActivityStatistics::class);
        $summary = $statistics->summary();
        $latest = $statistics->activationsYearOverYear()->last();

        $this->assertSame(2, $summary['activations']);
        $this->assertSame(1, $summary['previous_activations']);
        $this->assertSame(100.0, $summary['activations_change']);
        $this->assertCount(12, $statistics->activationsYearOverYear());
        $this->assertSame(2, $latest['current']);
        $this->assertSame(1, $latest['previous']);
    }

    public function testChangeIsEmptyWithoutAnEarlierYear()
    {
        $this->activate(User::factory()->create(), $this->reference(), now()->toDateString());

        $this->assertNull(app(ActivityStatistics::class)->summary()['activations_change']);
    }

    public function testQsosAndChasersAreSummedPerMonth()
    {
        $reference = $this->reference();
        [$user, $operator] = User::factory()->count(2)->create();
        $date = now()->startOfMonth()->toDateString();

        $this->activate($user, $reference, $date, 10, 4);
        $this->activate($operator, $reference, $date, 20, 6);
        $this->activate($user, $reference, $date, null, null);
        $this->activate($user, $reference, now()->subYears(3)->toDateString(), 999, 999);

        $statistics = app(ActivityStatistics::class);
        $qsos = $statistics->monthlyTotals('qso_count');
        $chasers = $statistics->monthlyTotals('chaser_count');

        $this->assertCount(24, $qsos);
        $this->assertSame(30, $qsos->last()['count']);
        $this->assertTrue($qsos->last()['is_peak']);
        $this->assertSame(30, $qsos->sum('count'));
        $this->assertSame(10, $chasers->sum('count'));
    }

    public function testLeaderboards()
    {
        $first = $this->reference();
        $second = $this->reference();
        $alpha = User::factory()->create(['callsign' => 'OH0AAA']);
        $bravo = User::factory()->create(['callsign' => 'OH0BBB']);
        $charlie = User::factory()->create(['callsign' => 'OH0CCC']);

        // Alpha first on both references, Bravo a joint first on the second one
        $this->activate($alpha, $first, '2020-01-01', 10);
        $this->activate($alpha, $first, '2021-01-01', 10);
        $this->activate($alpha, $second, '2020-02-01', 10);
        $this->activate($bravo, $second, '2020-02-01', 100);
        $this->activate($charlie, $first, '2022-01-01', 0);

        $statistics = app(ActivityStatistics::class);

        $this->assertEquals([
            ['rank' => 1, 'callsign' => 'OH0AAA', 'value' => 2],
            ['rank' => 2, 'callsign' => 'OH0BBB', 'value' => 1],
            ['rank' => 2, 'callsign' => 'OH0CCC', 'value' => 1],
        ], $statistics->mostReferences()->all());

        $this->assertEquals([
            ['rank' => 1, 'callsign' => 'OH0BBB', 'value' => 100],
            ['rank' => 2, 'callsign' => 'OH0AAA', 'value' => 30],
        ], $statistics->mostQsos()->all());

        $this->assertEquals([
            ['rank' => 1, 'callsign' => 'OH0AAA', 'value' => 2],
            ['rank' => 2, 'callsign' => 'OH0BBB', 'value' => 1],
        ], $statistics->mostFirstActivations()->all());
    }

    public function testDeletedReferencesAreNotCounted()
    {
        $deleted = $this->reference(['status' => 'deleted']);
        $this->activate(User::factory()->create(), $deleted, now()->toDateString(), 50, 50);

        $statistics = app(ActivityStatistics::class);

        $this->assertSame(0, $statistics->summary()['activations']);
        $this->assertTrue($statistics->mostQsos()->isEmpty());
    }

    public function testAlmostCompleteCounties()
    {
        $activated = ['latest_activation_date' => '2024-01-01'];

        // Lappi: 2 left, Kainuu: 1 left, Uusimaa: complete, Pirkanmaa: 6 left
        $this->reference(['county' => 'OH-LA', 'reference' => 'OHFF-0002']);
        $this->reference(['county' => 'OH-LA', 'reference' => 'OHFF-0001']);
        $this->reference(['county' => 'OH-LA', ...$activated]);
        $this->reference(['county' => 'OH-KU', 'reference' => 'OHFF-0003']);
        $this->reference(['county' => 'OH-KU', ...$activated]);
        $this->reference(['county' => 'OH-UU', ...$activated]);

        foreach (range(1, 6) as $number) {
            $this->reference(['county' => 'OH-PM']);
        }

        // Deleted and proposed references can't complete a county
        $this->reference(['county' => 'OH-UU', 'status' => 'deleted']);
        $this->reference(['county' => 'OH-UU', 'status' => 'proposed']);

        $counties = app(ActivityStatistics::class)->almostCompleteCounties();

        $this->assertSame(['Kainuu', 'Lappi'], $counties->pluck('name')->all());
        $this->assertSame(['OHFF-0001', 'OHFF-0002'], $counties[1]['remaining']->all());
        $this->assertSame(1, $counties[1]['activated']);
        $this->assertSame(3, $counties[1]['total']);

        $this->get('/statistics')->assertSee('filter%5Breference%5D=OHFF-0003', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function reference(array $attributes = []): Reference
    {
        return Reference::factory(['status' => 'active', 'latest_activation_date' => null, ...$attributes])
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();
    }

    private function activate(User $user, Reference $reference, string $date, ?int $qsos = 0, ?int $chasers = 0): void
    {
        DB::table('user_activations')->insert([
            'user_id' => $user->id,
            'reference_id' => $reference->id,
            'activation_date' => $date,
            'qso_count' => $qsos,
            'chaser_count' => $chasers,
        ]);
    }
}
