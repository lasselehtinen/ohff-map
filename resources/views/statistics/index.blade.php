<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OHFF statistics</title>
    <link rel="stylesheet" type="text/css" href="/css/statistics.css?v={{ filemtime(public_path('css/statistics.css')) }}" />
    <script src="/js/statistics.js?v={{ filemtime(public_path('js/statistics.js')) }}" defer></script>
  </head>
  <body>
    <main class="page">
      <header class="page-header">
        <h1>OHFF activity</h1>
        <a href="/">Back to the map</a>
      </header>

      <section class="tiles" aria-label="Last 12 months">
        <div class="tile tile-hero">
          <div class="tile-label">Active activators, last 12 months</div>
          <div class="hero-value">{{ number_format($summary['active_activators']) }}</div>
          <div class="tile-note">Unique callsigns</div>
        </div>
        <div class="tile">
          <div class="tile-label">New activators</div>
          <div class="tile-value">{{ number_format($summary['new_activators']) }}</div>
          <div class="tile-note">First logged activation in the last 12 months</div>
        </div>
        <div class="tile">
          <div class="tile-label">Activations, last 12 months</div>
          <div class="tile-value">{{ number_format($summary['activations']) }}</div>
          <div class="tile-note">
            @if (is_null($summary['activations_change']))
              No earlier year to compare with
            @else
              {{ $summary['activations_change'] > 0 ? '+' : '' }}{{ number_format($summary['activations_change'], 1) }}% vs the 12 months before ({{ number_format($summary['previous_activations']) }})
            @endif
          </div>
        </div>
      </section>

      <section class="card" aria-labelledby="activations-title">
        <h2 id="activations-title">Activations per month</h2>
        <p class="card-note">Each month next to the same month a year earlier. The latest month is still in progress.</p>
        @if ($activations->sum('current') + $activations->sum('previous') === 0)
          <p class="empty">No activation logs for this period yet.</p>
        @else
          <ul class="legend" aria-hidden="true">
            <li><span class="legend-key series-1"></span>Last 12 months</li>
            <li><span class="legend-key series-2"></span>A year earlier</li>
          </ul>
          <div class="columns">
            @foreach ($activations as $month)
              <div class="column-slot" data-tip="{{ $month['full_label'] }}|{{ number_format($month['current']) }} activations, a year earlier {{ number_format($month['previous']) }}" tabindex="0" aria-label="{{ $month['full_label'] }}: {{ number_format($month['current']) }} activations, a year earlier {{ number_format($month['previous']) }}">
                <div class="column-area column-pair">
                  <div class="column series-1" style="height: {{ $month['current_height'] }}%"></div>
                  <div class="column series-2" style="height: {{ $month['previous_height'] }}%"></div>
                </div>
                <div class="column-tick">{{ $month['label'] }}</div>
              </div>
            @endforeach
          </div>
          <details>
            <summary>Show as a table</summary>
            <div class="table-scroll table-narrow">
              <table>
                <thead><tr><th scope="col">Month</th><th scope="col" class="number">Activations</th><th scope="col" class="number">A year earlier</th></tr></thead>
                <tbody>
                  @foreach ($activations->reverse() as $month)
                    <tr><td>{{ $month['full_label'] }}</td><td class="number">{{ number_format($month['current']) }}</td><td class="number">{{ number_format($month['previous']) }}</td></tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </details>
        @endif
      </section>

      @include('statistics.columns', ['id' => 'activations-all-time', 'title' => 'Activations all time', 'note' => 'Per year, since the first logged activation. The latest year is still in progress.', 'period' => 'Year', 'unit' => 'activations', 'columns' => $activationsPerYear])

      <div class="chart-pair">
        @include('statistics.columns', ['id' => 'qsos', 'title' => 'QSOs per month', 'note' => 'Last 24 months.', 'period' => 'Month', 'unit' => 'QSOs', 'columns' => $qsos])
        @include('statistics.columns', ['id' => 'chasers', 'title' => 'Chasers per month', 'note' => 'Last 24 months.', 'period' => 'Month', 'unit' => 'chasers', 'columns' => $chasers])
      </div>

      <section class="card" aria-labelledby="leaderboards-title">
        <h2 id="leaderboards-title">Leaderboards</h2>
        <p class="card-note">All logged activations. Equal scores share a rank.</p>
        <div class="leaderboards">
          @include('statistics.leaderboard', ['title' => 'Most references activated', 'unit' => 'References', 'rows' => $mostReferences])
          @include('statistics.leaderboard', ['title' => 'Most QSOs', 'unit' => 'QSOs', 'rows' => $mostQsos])
          @include('statistics.leaderboard', ['title' => 'Most first activations', 'unit' => 'References', 'rows' => $mostFirstActivations])
        </div>
      </section>

      <section class="card" aria-labelledby="almost-title">
        <h2 id="almost-title">Almost complete counties</h2>
        <p class="card-note">Counties with five or fewer active references never activated.</p>
        @if ($almostComplete->isEmpty())
          <p class="empty">No county is that close right now.</p>
        @else
          <div class="table-scroll">
            <table>
              <thead>
                <tr><th scope="col">County</th><th scope="col" class="number">Activated</th><th scope="col">Still to activate</th></tr>
              </thead>
              <tbody>
                @foreach ($almostComplete as $county)
                  <tr>
                    <td>{{ $county['name'] }}</td>
                    <td class="number">{{ number_format($county['activated']) }} of {{ number_format($county['total']) }}</td>
                    <td class="references">
                      @foreach ($county['remaining'] as $reference)
                        <a href="/?filter%5Breference%5D={{ urlencode($reference) }}">{{ $reference }}</a>
                      @endforeach
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>

      <footer class="page-footer">Based on the WWFF activation logs collected so far. Deleted references are not counted.</footer>
    </main>
    <div id="tooltip" class="tooltip" role="status" hidden></div>
  </body>
</html>
