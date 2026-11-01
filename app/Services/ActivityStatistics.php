<?php

namespace App\Services;

use App\Models\Reference;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityStatistics
{
    /**
     * How many references a county can have left and still count as almost complete.
     */
    private const ALMOST_COMPLETE_LIMIT = 5;

    private const LEADERBOARD_SIZE = 10;

    /**
     * Activators and activations in the last 12 months, compared to the 12 months before.
     *
     * @return array{active_activators: int, new_activators: int, activations: int, previous_activations: int, activations_change: float|null}
     */
    public function summary(): array
    {
        $yearAgo = now()->subYear();
        $recent = $this->activations()->whereDate('activation_date', '>', $yearAgo->toDateString());

        $firstActivations = $this->activations()
            ->selectRaw('user_id, min(activation_date) as first_activation')
            ->groupBy('user_id')
            ->get();

        $current = $this->countActivations($recent);
        $previous = $this->countActivations(
            $this->activations()
                ->whereDate('activation_date', '>', $yearAgo->copy()->subYear()->toDateString())
                ->whereDate('activation_date', '<=', $yearAgo->toDateString())
        );

        return [
            'active_activators' => (clone $recent)->distinct()->count('user_id'),
            'new_activators' => $firstActivations
                ->filter(fn (object $row) => $row->first_activation > $yearAgo->toDateString())
                ->count(),
            'activations' => $current,
            'previous_activations' => $previous,
            'activations_change' => $previous === 0 ? null : round(($current - $previous) / $previous * 100, 1),
        ];
    }

    /**
     * Activations per month for the last 12 months next to the same months a year earlier.
     *
     * @return Collection<int, array{label: string, full_label: string, current: int, previous: int, current_height: float, previous_height: float}>
     */
    public function activationsYearOverYear(): Collection
    {
        $counts = $this->monthlyRows()
            ->unique(fn (object $row) => $row->reference_id.'|'.$row->activation_date)
            ->countBy(fn (object $row) => substr((string) $row->activation_date, 0, 7));

        $largest = max($counts->max() ?? 0, 1);

        return $this->months(12)->map(function (Carbon $month) use ($counts, $largest) {
            $current = $counts->get($month->format('Y-m'), 0);
            $previous = $counts->get($month->copy()->subYear()->format('Y-m'), 0);

            return [
                'label' => $month->format('M'),
                'full_label' => $month->format('M Y'),
                'current' => $current,
                'previous' => $previous,
                'current_height' => round($current / $largest * 100, 2),
                'previous_height' => round($previous / $largest * 100, 2),
            ];
        });
    }

    /**
     * Totals of a logged count per month for the last 24 months.
     *
     * @param  'qso_count'|'chaser_count'  $column
     * @return Collection<int, array{label: string, tick: string|null, count: int, height: float, is_peak: bool}>
     */
    public function monthlyTotals(string $column): Collection
    {
        $totals = $this->monthlyRows()
            ->groupBy(fn (object $row) => substr((string) $row->activation_date, 0, 7))
            ->map(fn (Collection $rows) => (int) $rows->sum($column));

        $peak = $totals->max() ?? 0;
        $largest = max($peak, 1);
        $peakShown = false;

        return $this->months(24)->map(function (Carbon $month) use ($totals, $largest, $peak, &$peakShown) {
            $count = $totals->get($month->format('Y-m'), 0);
            $isPeak = $peak > 0 && $count === $peak && ! $peakShown;
            $peakShown = $peakShown || $isPeak;

            return [
                'label' => $month->format('M Y'),
                'tick' => in_array($month->month, [1, 7]) ? $month->format('M Y') : null,
                'count' => $count,
                'height' => round($count / $largest * 100, 2),
                'is_peak' => $isPeak,
            ];
        });
    }

    /**
     * Callsigns with the most distinct references activated, all time.
     *
     * @return Collection<int, array{rank: int, callsign: string, value: int}>
     */
    public function mostReferences(): Collection
    {
        return $this->leaderboard(
            $this->activations()->selectRaw('users.callsign, count(distinct user_activations.reference_id) as value')
        );
    }

    /**
     * Callsigns with the most QSOs, all time.
     *
     * @return Collection<int, array{rank: int, callsign: string, value: int}>
     */
    public function mostQsos(): Collection
    {
        return $this->leaderboard(
            $this->activations()->selectRaw('users.callsign, sum(user_activations.qso_count) as value')
        );
    }

    /**
     * Callsigns that were first to activate the most references. Everyone who
     * activated a reference on its earliest logged date gets the credit.
     *
     * @return Collection<int, array{rank: int, callsign: string, value: int}>
     */
    public function mostFirstActivations(): Collection
    {
        $firstDates = $this->activations()
            ->selectRaw('user_activations.reference_id as first_reference_id, min(user_activations.activation_date) as first_date')
            ->groupBy('user_activations.reference_id');

        return $this->leaderboard(
            $this->activations()
                ->joinSub($firstDates, 'firsts', function ($join) {
                    $join->on('firsts.first_reference_id', '=', 'user_activations.reference_id')
                        ->on('firsts.first_date', '=', 'user_activations.activation_date');
                })
                ->selectRaw('users.callsign, count(distinct user_activations.reference_id) as value')
        );
    }

    /**
     * Counties with only a few active references left to activate, closest to complete first.
     *
     * @return Collection<int, array{name: mixed, total: int, activated: int, remaining: Collection<int, mixed>}>
     */
    public function almostCompleteCounties(): Collection
    {
        $names = config('counties');

        return Reference::query()
            ->where('status', 'active')
            ->whereIn('county', array_keys($names))
            ->orderBy('reference')
            ->get(['county', 'reference', 'latest_activation_date'])
            ->groupBy('county')
            ->map(function (Collection $references, string $county) use ($names) {
                $remaining = $references->whereNull('latest_activation_date')->pluck('reference')->values();

                return [
                    'name' => $names[$county],
                    'total' => $references->count(),
                    'activated' => $references->count() - $remaining->count(),
                    'remaining' => $remaining,
                ];
            })
            ->filter(fn (array $county) => $county['remaining']->isNotEmpty()
                && $county['remaining']->count() <= self::ALMOST_COMPLETE_LIMIT)
            ->sortBy([
                fn (array $a, array $b) => $a['remaining']->count() <=> $b['remaining']->count(),
                ['name', 'asc'],
            ])
            ->values();
    }

    /**
     * Logged activations with a callsign, on references that aren't deleted.
     */
    private function activations(): Builder
    {
        return DB::table('user_activations')
            ->join('references', 'references.id', '=', 'user_activations.reference_id')
            ->join('users', 'users.id', '=', 'user_activations.user_id')
            ->where('references.status', '!=', 'deleted')
            ->whereNotNull('users.callsign')
            ->whereNotNull('user_activations.activation_date')
            ->whereDate('user_activations.activation_date', '<=', now()->toDateString());
    }

    /**
     * Activation rows of the last 24 months.
     *
     * @return Collection<int, \stdClass>
     */
    private function monthlyRows(): Collection
    {
        return $this->activations()
            ->whereDate('activation_date', '>=', $this->months(24)->first()->toDateString())
            ->get(['reference_id', 'activation_date', 'qso_count', 'chaser_count']);
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function months(int $count): Collection
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $monthsAgo) => Carbon::now()->startOfMonth()->subMonths($monthsAgo));
    }

    /**
     * An activation with several operators counts once.
     */
    private function countActivations(Builder $query): int
    {
        return $query->get(['reference_id', 'activation_date'])
            ->unique(fn (object $row) => $row->reference_id.'|'.$row->activation_date)
            ->count();
    }

    /**
     * Rank callsigns by value. Equal values share a rank.
     *
     * @return Collection<int, array{rank: int, callsign: string, value: int}>
     */
    private function leaderboard(Builder $query): Collection
    {
        $rank = 0;
        $previous = null;

        return $query->groupBy('users.callsign')
            ->having('value', '>', 0)
            ->orderByDesc('value')
            ->orderBy('users.callsign')
            ->limit(self::LEADERBOARD_SIZE)
            ->get()
            ->values()
            ->map(function (object $row, int $index) use (&$rank, &$previous) {
                $value = (int) $row->value;
                $rank = $value === $previous ? $rank : $index + 1;
                $previous = $value;

                return ['rank' => (int) $rank, 'callsign' => (string) $row->callsign, 'value' => $value];
            });
    }
}
