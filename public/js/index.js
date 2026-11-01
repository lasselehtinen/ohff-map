const MAP_CENTER = [64.95122, 27.41089];
const MAP_ZOOM = 5;
const CLOSE_ZOOM = 12;
const AREA_STYLE = { fillColor: "#12613c", color: "#12613c", weight: 1 };

function escapeHtml(value) {
  const element = document.createElement("div");
  element.textContent = value ?? "";

  return element.innerHTML.replaceAll('"', "&quot;");
}

function link(url, label, newWindow = true) {
  const target = newWindow ? ' target="_blank" rel="noopener"' : "";

  return `<a href="${escapeHtml(url)}"${target}>${escapeHtml(label)}</a><br/>`;
}

function popupContent(properties) {
  const reference = properties.reference;
  let content = `<h1>${escapeHtml(reference)}</h1><h2>${escapeHtml(properties.name)}</h2><div id="bodyContent">`;

  if (properties.latest_activation_date) {
    content += `<p>Latest activation: ${escapeHtml(properties.latest_activation_date)} by ${escapeHtml(properties.latest_activator)}</p>`;
  }

  content += link(`https://wwff.co/directory/?showRef=${encodeURIComponent(reference)}`, "WWFF");

  if (properties.wdpa_id) {
    content += link(`https://www.protectedplanet.net/${encodeURIComponent(properties.wdpa_id)}`, "Protected Planet");
  }

  if (properties.is_natura_2000_area) {
    content += link(properties.paikkatietoikkuna_link, "Paikkatietoikkuna");
  } else {
    content += link(properties.karttapaikka_link, "Kansalaisen karttapaikka");
  }

  // Link to area boundary maps
  content += link(`https://oh7rx.kapsi.fi/ohff/map.php?wdpaid=${encodeURIComponent(properties.wdpa_id)}`, "Informative: Boundaries on topographic map");

  // External navigation
  content += "For navigation: " + link(`https://www.google.com/maps/place/${properties.latitude},${properties.longitude}`, "Google Maps");

  // Add link to create github issue
  content += "<br/>" + link(`https://github.com/lasselehtinen/ohff-map/issues/new?template=reporting-issue-with-reference-information.md&title=${encodeURIComponent("Problem with " + reference)}`, "Report issue", false);
  content += "</div>";

  // Add extra notification if the area is a Natura 2000 area
  if (properties.is_natura_2000_area) {
    content += "<p>⚠️ The area is a Natura 2000 area.</p><p>Check the area from Paikkatietoikkuna.</p>";
  }

  return content;
}

function addButton(map, label, title, position, onClick) {
  const Button = L.Control.extend({
    onAdd() {
      const button = L.DomUtil.create("button", "custom-map-control-button");
      button.type = "button";
      button.textContent = label;
      button.title = title;
      L.DomEvent.disableClickPropagation(button);
      L.DomEvent.on(button, "click", onClick);

      return button;
    },
  });

  new Button({ position }).addTo(map);
}

function panToCurrentLocation(map) {
  const showError = (message) => L.popup().setLatLng(map.getCenter()).setContent(message).openOn(map);

  if (!navigator.geolocation) {
    showError("Your browser does not support geolocation.");
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (position) => map.setView([position.coords.latitude, position.coords.longitude], CLOSE_ZOOM),
    () => showError("Could not get your location."),
  );
}

function toggleFullscreen(map) {
  if (document.fullscreenElement) {
    document.exitFullscreen();
  } else {
    map.getContainer().requestFullscreen();
  }
}

// Thousands of DOM markers make panning sluggish, so markers are drawn on a
// single canvas instead. The icons are rasterized once to keep redraws cheap.
const ICON_SIZE = 32;
const ICON_RESOLUTION = 2;

const ReferenceMarker = L.CircleMarker.extend({
  options: { radius: ICON_SIZE / 2, bubblingMouseEvents: false },

  // The icon sits above the point it marks, with its tip on that point.
  _updateBounds() {
    const half = ICON_SIZE / 2;
    const { x, y } = this._point;

    this._pxBounds = L.bounds([x - half, y - ICON_SIZE], [x + half, y]);
  },

  _updatePath() {
    const renderer = this._renderer;

    if (renderer._drawing && !this._empty()) {
      const { min } = this._pxBounds;
      renderer._ctx.drawImage(this.options.image, min.x, min.y, ICON_SIZE, ICON_SIZE);
    }
  },

  _containsPoint(point) {
    return this._pxBounds.contains(point);
  },
});

async function rasterizeIcon(url) {
  const image = new Image();
  image.src = url;
  await image.decode();

  const canvas = document.createElement("canvas");
  canvas.width = canvas.height = ICON_SIZE * ICON_RESOLUTION;
  canvas.getContext("2d").drawImage(image, 0, 0, canvas.width, canvas.height);

  return canvas;
}

async function rasterizeIcons(features) {
  const urls = [...new Set(features.map((feature) => feature.properties.icon))];
  const icons = await Promise.all(urls.map(rasterizeIcon));

  return new Map(urls.map((url, index) => [url, icons[index]]));
}

async function loadReferences(map) {
  try {
    const response = await fetch("/geojson" + window.location.search);

    if (!response.ok) {
      throw new Error(`Unexpected status ${response.status}`);
    }

    const collection = await response.json();
    const icons = await rasterizeIcons(collection.features);

    // Later markers are drawn on top, so southern markers overlap northern ones
    collection.features.sort((a, b) => b.geometry.coordinates[1] - a.geometry.coordinates[1]);

    L.geoJSON(collection, {
      style: AREA_STYLE,
      pointToLayer: (feature, latlng) => new ReferenceMarker(latlng, { image: icons.get(feature.properties.icon) }),
      onEachFeature(feature, layer) {
        layer.bindTooltip(feature.properties.reference, { direction: "top", offset: [0, -ICON_SIZE] });
        layer.on("click", () => {
          L.popup({ maxWidth: 400, offset: [0, -ICON_SIZE + 2] })
            .setLatLng(layer.getLatLng())
            .setContent(popupContent(feature.properties))
            .openOn(map);
        });
        layer.on("dblclick", () => map.setView(layer.getLatLng(), CLOSE_ZOOM));
      },
    }).addTo(map);
  } catch (error) {
    console.error(error);
    L.popup().setLatLng(map.getCenter()).setContent("Could not load the references. Please try again later.").openOn(map);
  }
}

// Submit only the filters that have a value, which keeps the URL clean
function submitFilledFilters(event) {
  event.preventDefault();

  const form = event.target;
  const params = new URLSearchParams();

  for (const [name, value] of new FormData(form)) {
    if (value.trim() !== "") {
      params.append(name, value.trim());
    }
  }

  const query = params.toString();

  window.location.assign(form.getAttribute("action") + (query ? `?${query}` : ""));
}

function initMap() {
  // The padding keeps markers drawn just outside the view, so they don't pop in while panning
  const map = L.map("map", { renderer: L.canvas({ padding: 0.5 }) }).setView(MAP_CENTER, MAP_ZOOM);

  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  L.control.scale().addTo(map);

  addButton(map, "Pan to Current Location", "Pan to current location", "topleft", () => panToCurrentLocation(map));
  addButton(map, "⛶", "Toggle fullscreen", "topright", () => toggleFullscreen(map));
  document.addEventListener("fullscreenchange", () => map.invalidateSize());

  loadReferences(map);
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelector("body > form").addEventListener("submit", submitFilledFilters);
  initMap();
});
