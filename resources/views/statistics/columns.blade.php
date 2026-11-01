<section class="card" aria-labelledby="{{ $id }}-title">
  <h2 id="{{ $id }}-title">{{ $title }}</h2>
  <p class="card-note">Last 24 months.</p>
  @if ($months->sum('count') === 0)
    <p class="empty">No activation logs for this period yet.</p>
  @else
    <div class="columns">
      @foreach ($months as $month)
        <div class="column-slot" data-tip="{{ $month['label'] }}|{{ number_format($month['count']) }} {{ $unit }}" tabindex="0" aria-label="{{ $month['label'] }}: {{ number_format($month['count']) }} {{ $unit }}">
          <div class="column-area">
            @if ($month['is_peak'])
              <span class="column-peak" style="bottom: calc({{ $month['height'] }}% + 4px)">{{ number_format($month['count']) }}</span>
            @endif
            @if ($month['count'] > 0)
              <div class="column series-1" style="height: {{ $month['height'] }}%"></div>
            @endif
          </div>
          <div class="column-tick">{{ $month['tick'] }}</div>
        </div>
      @endforeach
    </div>
    <details>
      <summary>Show as a table</summary>
      <div class="table-scroll table-narrow">
        <table>
          <thead><tr><th scope="col">Month</th><th scope="col" class="number">{{ ucfirst($unit) }}</th></tr></thead>
          <tbody>
            @foreach ($months->reverse() as $month)
              <tr><td>{{ $month['label'] }}</td><td class="number">{{ number_format($month['count']) }}</td></tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </details>
  @endif
</section>
