<!DOCTYPE html>
<html>
  <head>
    <title>OHFF-kartta</title>
    <link rel="stylesheet" type="text/css" href="/vendor/leaflet/leaflet.css" />
    <link rel="stylesheet" type="text/css" href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}" />
    <script src="/vendor/leaflet/leaflet.js"></script>
    <script src="/js/index.js?v={{ filemtime(public_path('js/index.js')) }}"></script>
  </head>
  <body>
    <form action="/" method="get">
        <label for="activated_by">Activated by:</label>
        <input type="text" id="activated_by" name="filter[activated_by]" size="10" placeholder="Callsign" value="{{strtoupper($request->input('filter.activated_by'))}}">
        
        <label for="not_activated_by">Not activated by:</label>      
        <input type="text" id="not_activated_by" name="filter[not_activated_by]" size="10" placeholder="Callsign" value="{{strtoupper($request->input('filter.not_activated_by'))}}">

        <label for="reference">Reference:</label>      
        <input type="text" id="reference" name="filter[reference]" size="10" value="{{$request->input('filter.reference')}}">

        <label for="not_activated">Not activated by anyone</label>
        <input type="checkbox" id="not_activated" name="filter[not_activated]" @checked($request->filled('filter.not_activated'))>

        <label for="activited_this_year">Activated this year</label>
        <input type="checkbox" id="activited_this_year" name="filter[activited_this_year]" @checked($request->filled('filter.activited_this_year'))>

        <label for="approval_status">Approval status</label>
        <select id="approval_status" name="filter[approval_status]">
          <option value=""></option>
          <option value="received" @selected($request->input('filter.approval_status') === 'received')>Received</option>
          <option value="approved" @selected($request->input('filter.approval_status') === 'approved')>Approved</option>
          <option value="saved" @selected($request->input('filter.approval_status') === 'saved')>Saved</option>
        </select>

        <input type="submit" id="submit"/>
    </form>

    <div class="legend">
      Not activated: <img src="/images/markers/tree.svg" width="16" height="16">
      Activated this year: <img src="/images/markers/blue.svg" width="16" height="16">
      Activated 1 year ago: <img src="/images/markers/green.svg" width="16" height="16">
      Activated 2 years ago: <img src="/images/markers/yellow.svg" width="16" height="16">
      Activated 3 years ago: <img src="/images/markers/orange.svg" width="16" height="16">
      Activated 4++ years ago: <img src="/images/markers/red.svg" width="16" height="16">
      Suggested: <img src="/images/markers/purple.svg" width="16" height="16">
      <a href="/statistics">Statistics</a>
    </div>

    <div id="map"></div>
  </body>
</html>