<div id="leaflet-map-{{ $id ?? 'default' }}" style="height: 200px; width: 100%;"></div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    (function(){
        var containerId = 'leaflet-map-{{ $id ?? 'default' }}';
        var map = L.map(containerId, {
            scrollWheelZoom: false
        }).setView([55.76, 37.64], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        @if(isset($geometry) && !empty($geometry))
        var geo = {!! json_encode($geometry) !!};

        if (geo.type === 'Point') {
            var coords = geo.coordinates;
            var point = [coords[1], coords[0]];
            L.marker(point).addTo(map);
            map.setView(point, 14);
        } else if (geo.type === 'MultiPolygon') {
            console.log("MultiPolygon coordinates:", geo.coordinates);
            var multiPolyCoords = geo.coordinates.map(function(polygon) {
                return polygon.map(function(ring) {
                    return ring.map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                });
            });
            var polygons = multiPolyCoords.map(function(rings) {
                return L.polygon(rings, { color: 'blue' });
            });
            var multiPolygonGroup = L.featureGroup(polygons).addTo(map);
            map.fitBounds(multiPolygonGroup.getBounds());
        }

        @endif
    })();
</script>
