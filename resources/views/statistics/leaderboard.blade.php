<div class="leaderboard">
  <h3>{{ $title }}</h3>
  @if ($rows->isEmpty())
    <p class="empty">No activation logs yet.</p>
  @else
    <table>
      <thead>
        <tr><th scope="col" class="number">#</th><th scope="col">Callsign</th><th scope="col" class="number">{{ $unit }}</th></tr>
      </thead>
      <tbody>
        @foreach ($rows as $row)
          <tr>
            <td class="number rank">{{ $row['rank'] }}</td>
            <td>{{ $row['callsign'] }}</td>
            <td class="number">{{ number_format($row['value']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
