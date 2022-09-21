<!DOCTYPE html>
<html>
  <head>
    <title>OHFF-kartta</title>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <script src="js/index.js"></script>
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
        <input type="checkbox" id="not_activated" name="filter[not_activated]">

        <label for="approval_status">Approval status</label>
        <select id="approval_status" name="filter[approval_status]">
          <option value=""></option>
          <option value="received">Received</option>
          <option value="approved">Approved</option>
          <option value="saved">Saved</option>
        </select>

        <input type="submit" id="submit"/>
    </form>

    <div class="legend">
      Not activated: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/tree.png" width="16" height="16">
      Activated this year: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/blue.png" width="16" height="16">
      Activated 1 year ago: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/green.png" width="16" height="16">
      Activated 2 years ago: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/yellow.png" width="16" height="16">
      Activated 3 years ago: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/orange.png" width="16" height="16">
      Activated 4++ years ago: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png" width="16" height="16">
      Suggested: <img src="http://maps.google.com/intl/en_us/mapfiles/ms/micons/purple.png" width="16" height="16">
    </div>

    <div id="map"></div>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjZYYdCJxkqonGrjoBwdwZpZtOjpjHtuA&callback=initMap&v=weekly" async></script>
  </body>
</html>