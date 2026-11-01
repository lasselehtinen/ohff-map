<section class="card" aria-labelledby="{{ $id }}-title">
  <h2 id="{{ $id }}-title">{{ $title }}</h2>
  <p class="card-note">{{ $note }}</p>
  @if ($columns->sum('count') === 0)
    <p class="empty">No activation logs for this period yet.</p>
  @else
    <div class="columns">
      @foreach ($columns as $column)
        <div class="column-slot" data-tip="{{ $column['label'] }}|{{ number_format($column['count']) }} {{ $unit }}" tabindex="0" aria-label="{{ $column['label'] }}: {{ number_format($column['count']) }} {{ $unit }}">
          <div class="column-area">
            @if ($column['is_peak'])
              <span class="column-peak" style="bottom: calc({{ $column['height'] }}% + 4px)">{{ number_format($column['count']) }}</span>
            @endif
            @if ($column['count'] > 0)
              <div class="column series-1" style="height: {{ $column['height'] }}%"></div>
            @endif
          </div>
          <div class="column-tick">{{ $column['tick'] }}</div>
        </div>
      @endforeach
    </div>
    <details>
      <summary>Show as a table</summary>
      <div class="table-scroll table-narrow">
        <table>
          <thead><tr><th scope="col">{{ $period }}</th><th scope="col" class="number">{{ ucfirst($unit) }}</th></tr></thead>
          <tbody>
            @foreach ($columns->reverse() as $column)
              <tr><td>{{ $column['label'] }}</td><td class="number">{{ number_format($column['count']) }}</td></tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </details>
  @endif
</section>
