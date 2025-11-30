<div id="map" style="width: 100%; height: 600px;"></div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const defaultLatLng = [43.340485, 52.857024];
        var geojsonData = {!! isset($geoObject->geometry) ? $geoObject->geometry : 'null' !!};

        const map = L.map('map').setView(defaultLatLng, 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);
        var marker, polygon;

        if (geojsonData && geojsonData.type) {
            if (geojsonData.type === 'Point') {
                let coords = geojsonData.coordinates;
                let swappedCoords = [coords[1], coords[0]];
                marker = L.marker(swappedCoords).addTo(drawnItems);
                map.setView(swappedCoords, 14);
            } else if (geojsonData.type === 'MultiPolygon') {
                var multiPolyCoords = geojsonData.coordinates.map(function(polygon) {
                    return polygon.map(function(ring) {
                        return ring.map(function(coord) {
                            return [coord[1], coord[0]];
                        });
                    });
                });
                var polygons = multiPolyCoords.map(function(rings) {
                    return L.polygon(rings, { color: 'blue' }).addTo(drawnItems);
                });
                var multiPolygonGroup = L.featureGroup(polygons).addTo(map);
                map.fitBounds(multiPolygonGroup.getBounds());
            }
        } else {
            marker = L.marker(defaultLatLng).addTo(drawnItems)
                .bindPopup('<b>Default Location</b>').openPopup();
        }

        // Optionally add JS to sync map clicks with Orchid controls.
    });
</script>
