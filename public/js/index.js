let map, InfoWindow;

function initMap() {
  // Load map and GeoJSON
  map = new google.maps.Map(document.getElementById("map"), {
    zoom: 5,
    center: { lat: 64.95122, lng: 27.41089 },
    mapTypeId: google.maps.MapTypeId.TERRAIN
  });

  infoWindow = new google.maps.InfoWindow();

  const locationButton = document.createElement("button");

  locationButton.textContent = "Pan to Current Location";
  locationButton.classList.add("custom-map-control-button");
  map.controls[google.maps.ControlPosition.TOP_LEFT].push(locationButton);

  locationButton.addEventListener("click", () => {
    // Try HTML5 geolocation.
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const pos = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          };

          map.setZoom(12);
          map.setCenter(pos);
        },
        () => {
          handleLocationError(true, infoWindow, map.getCenter());
        }
      );
    } else {
      // Browser doesn't support Geolocation
      handleLocationError(false, infoWindow, map.getCenter());
    }
  });

  const queryString = window.location.search;

  map.addListener("idle", function() {
    if (map.getZoom() > 7) {
      // Remove all existing polygons
      map.data.forEach(function(feature) {
          if (feature.getGeometry().getType() == 'Polygon' || feature.getGeometry().getType() == 'MultiPolygon') {
            map.data.remove(feature);
          }
      });

      map.data.loadGeoJson("/geojson?zoom=" + map.getZoom() + "&filter[within]=" + map.getBounds().getSouthWest()+ ";" + map.getBounds().getNorthEast() + queryString);
    }
  });

  map.data.loadGeoJson("/geojson" + queryString);

  // Read icon from property
  map.data.setStyle(function(feature) {
    return {
      icon:feature.getProperty('icon'),
      fillColor: "#12613c",
      strokeWeight: 1
    };
  });

  // When the user clicks, open an infowindow
  var infowindow = new google.maps.InfoWindow();

  map.data.addListener('click', function(event) {
    contentString =
    '<h1>'+ event.feature.getProperty("reference") +'</h1>' +
    '<h2>'+ event.feature.getProperty("name") +'</h2>' +
    '<div id="bodyContent">' +
    '<p>Latest activation: ' + event.feature.getProperty("latest_activation_date") + ' by ' + event.feature.getProperty("latest_activator") + '</p>' +
    '<p><a href='+ event.feature.getProperty("karttapaikka_link") + '" target="_new">Kansalaisen karttapaikka</a><br/>';

    if (event.feature.getProperty("wdpa_id")) {
      contentString += '<a href=https://www.protectedplanet.net/'+ event.feature.getProperty("wdpa_id") + '" target="_new">Protected Planet</a><br/>';
    }

    // Add link to create github issue
    contentString += '<a href="https://github.com/lasselehtinen/ohff-map/issues/new?template=reporting-issue-with-reference-information.md&title=&title=Problem%20with%20' + event.feature.getProperty("reference") + '">Report issue</a></br>'

    contentString += '</p>'
  

    infowindow.setContent(contentString); // show the html variable in the infowindow
    
    if (event.feature.getGeometry().getType() == 'Point') {
      infowindow.setPosition(event.feature.getGeometry().get()); // anchor the infowindow at the marker
      infowindow.setOptions({pixelOffset: new google.maps.Size(0,-30)}); // move the infowindow up slightly to the top of the marker icon
    }

    infowindow.open(map);
  });

  map.data.addListener('dblclick', function (event) 
  {
      map.panTo(event.latLng);
      map.setZoom(12);
  });
}