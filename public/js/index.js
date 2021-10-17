let map;

function initMap() {
  // Load map and GeoJSON
  map = new google.maps.Map(document.getElementById("map"), {
    zoom: 5,
    center: { lat: 64.95122, lng: 27.41089 },
    mapTypeId: google.maps.MapTypeId.TERRAIN
  });

  const queryString = window.location.search;

  map.data.loadGeoJson("/geojson?" + queryString);

  // Read icon from property
  map.data.setStyle(function(feature) {
    return {icon:feature.getProperty('icon')};
  });

  // When the user clicks, open an infowindow
  var infowindow = new google.maps.InfoWindow();

  map.data.addListener('click', function(event) {
    // in the geojson feature that was clicked, get the "place" and "mag" attributes
    let reference = event.feature.getProperty("reference");
    let name = event.feature.getProperty("name");
    let latest_activation_date = event.feature.getProperty("latest_activation_date");
    let latest_activator = event.feature.getProperty("latest_activator");
    
    let html = '<strong>' + reference + ' - ' + name + '</strong><p> Latest activation: ' + latest_activation_date + ' by ' + latest_activator  + '</p>';
    
    infowindow.setContent(html); // show the html variable in the infowindow
    infowindow.setPosition(event.feature.getGeometry().get()); // anchor the infowindow at the marker
    infowindow.setOptions({pixelOffset: new google.maps.Size(0,-30)}); // move the infowindow up slightly to the top of the marker icon
    infowindow.open(map);
  });
}