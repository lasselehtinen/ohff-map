<?php

namespace App\Http\Controllers;

use App\Services\ActivityStatistics;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    /**
     * Display the public activity statistics page.
     */
    public function index(ActivityStatistics $statistics): View
    {
        return view('statistics.index', [
            'summary' => $statistics->summary(),
            'activations' => $statistics->activationsYearOverYear(),
            'qsos' => $statistics->monthlyTotals('qso_count'),
            'chasers' => $statistics->monthlyTotals('chaser_count'),
            'mostReferences' => $statistics->mostReferences(),
            'mostQsos' => $statistics->mostQsos(),
            'mostFirstActivations' => $statistics->mostFirstActivations(),
            'almostComplete' => $statistics->almostCompleteCounties(),
        ]);
    }
}
