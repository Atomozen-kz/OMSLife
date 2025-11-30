@php
    $geoObj = $geoObject ?? null;
    $geojsonData = ($geoObj && isset($geoObj->geometry)) ? json_decode($geoObj->geometry) : null;
@endphp
    <!DOCTYPE html>
<html lang="en">
<head>
    <base target="_top">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leaflet GeoJSON Example</title>
    <link rel="shortcut icon" type="image/x-icon" href="docs/images/favicon.ico" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: #f7f7f7;
            color: #333;
        }
        .wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            gap: 20px;
        }
        #map {
            width: 800px;
            height: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .controls-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .controls-container h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .mode-selection,
        .manual-input,
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }
        .manual-input label {
            display: flex;
            flex-direction: column;
            font-weight: 500;
        }
        .manual-input input,
        .manual-input select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 5px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background: #3498db;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        #loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            z-index: 1001;
            display: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        #message button {
            margin-left: 10px;
            background: transparent;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: inherit;
        }
    </style>
</head>
<body autocomplete="off">
<div id="loader"></div>
<div id="message">
    <span id="message-text"></span>
    <button id="close-message">&times;</button>
</div>
<div class="wrapper">
    <div id="map"></div>
    <div class="controls-container">
        <h5>Режим</h5>
        <div class="mode-selection">
            <label>
                <input type="radio" name="mode" value="point" id="mode-point"> Точка
            </label>
            <label>
                <input type="radio" name="mode" value="polygon" id="mode-polygon"> Полигон
            </label>
        </div>
        <h5>Тип объекта</h5>
        <div class="manual-input">
            <label>
                Выберите тип:
                <select id="type">
                    @foreach($promzonaTypes as $type)
                        <option value="{{ $type->id }}" @if($geoObj && $geoObj->id_type == $type->id) selected @endif>
                            {{ $type->name_ru }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
        <h5>Название</h5>
        <div class="manual-input">
            <label>
                <input type="text" id="name" placeholder="Введите название" value="{{ $geoObj->name ?? '' }}">
            </label>
        </div>
        <h5>Координаты</h5>
        <div class="manual-input">
            <label>
                Широта:
                <input type="number" step="any" id="latitude" placeholder="Введите широту">
            </label>
            <label>
                Долгота:
                <input type="number" step="any" id="longitude" placeholder="Введите долготу">
            </label>
        </div>
        <h5>Родительский объект</h5>
        <div class="manual-input">
            <label>
                Выберите родительский объект:
                <select id="parent_id" name="parent_id">
                    <option value="">Без родителя</option>
                    @foreach($parentOptions as $parent)
                        <option value="{{ $parent->id }}">
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="buttons">
            <button type="button" id="clear-map">Очистить</button>
            <button type="button" id="save-geometry">Сохранить</button>
        </div>
    </div>
</div>
<script>
    document.getElementById('close-message').addEventListener('click', function() {
        document.getElementById('message').style.display = 'none';
    });
    setTimeout(function() {
        var geoObj = {!! json_encode($geoObj) !!};
        var geojsonData = {!! json_encode($geojsonData) !!};
        const defaultLatLng = [43.340485, 52.857024];
        const map = L.map('map').setView(defaultLatLng, 13);
        const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);
        var currentMode = 'point';
        var marker, polygon;
        if (geojsonData && geojsonData.type) {
            document.getElementById('name').value = geoObj.name;
            if (geojsonData.type === 'Point') {
                let coords = geojsonData.coordinates;
                let swappedCoords = [coords[1], coords[0]];
                marker = L.marker(swappedCoords).addTo(drawnItems);
                map.setView(swappedCoords, 14);
                document.getElementById('mode-point').checked = true;
                currentMode = 'point';
                document.getElementById('latitude').value = swappedCoords[0];
                document.getElementById('longitude').value = swappedCoords[1];
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
                document.getElementById('mode-polygon').checked = true;
                currentMode = 'polygon';
            }
        } else {
            marker = L.marker(defaultLatLng).addTo(drawnItems).bindPopup('<b>Default Location</b>').openPopup();
            document.getElementById('mode-point').checked = true;
            currentMode = 'point';
            document.getElementById('latitude').value = defaultLatLng[0];
            document.getElementById('longitude').value = defaultLatLng[1];
        }
        document.querySelectorAll('input[name="mode"]').forEach(function(el) {
            el.addEventListener('change', function() {
                currentMode = this.value;
                drawnItems.clearLayers();
                marker = null;
                polygon = null;
            });
        });
        let isCleared = false;
        document.getElementById('clear-map').addEventListener('click', function() {
            drawnItems.clearLayers();
            marker = null;
            polygon = null;
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            isCleared = true;
            setTimeout(() => { isCleared = false; }, 500);
        });
        map.on('click', function(e) {
            if (isCleared) return;
            if (currentMode === 'point') {
                drawnItems.clearLayers();
                marker = L.marker(e.latlng).addTo(drawnItems);
                document.getElementById('latitude').value = e.latlng.lat;
                document.getElementById('longitude').value = e.latlng.lng;
            }
        });
        var polygonDrawer = new L.Draw.Polygon(map, {
            allowIntersection: false,
            showArea: true,
            drawError: {
                color: 'red',
                message: '<strong>Ошибка:</strong> Полигон не может пересекаться'
            },
            shapeOptions: {
                color: 'blue'
            }
        });
        map.on('click', function(e) {
            if (isCleared) return;
            if (currentMode === 'polygon' && !polygonDrawer._drawing) {
                polygonDrawer.enable();
            }
        });
        map.on('draw:created', function(e) {
            if (currentMode === 'polygon') {
                drawnItems.clearLayers();
                polygon = e.layer;
                drawnItems.addLayer(polygon);
            }
        });
        function updateMarkerFromInputs() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);
            if (isNaN(lat) || isNaN(lng)) return;
            currentMode = 'point';
            document.getElementById('mode-point').checked = true;
            drawnItems.clearLayers();
            marker = L.marker([lat, lng]).addTo(drawnItems);
            map.setView([lat, lng], 14);
        }
        document.getElementById('latitude').addEventListener('change', updateMarkerFromInputs);
        document.getElementById('longitude').addEventListener('change', updateMarkerFromInputs);
        document.getElementById('save-geometry').addEventListener('click', function() {
            // Пробуем сформировать данные геометрии, если они есть
            let geojsonData = null;
            if (marker) {
                var geojson = marker.toGeoJSON();
                geojson.geometry.type = "Point";
                geojsonData = JSON.stringify(geojson.geometry);
            } else if (polygon) {
                var geojson = polygon.toGeoJSON();
                var coordinates = geojson.geometry.coordinates.map(function(ring) {
                    return ring.map(function(coord) {
                        return [coord[0], coord[1]];
                    });
                });
                var multipolygon = {
                    type: "MultiPolygon",
                    coordinates: [[coordinates]]
                };
                geojsonData = JSON.stringify(multipolygon);
            }

            // Формируем полезную нагрузку запроса
            let payload = {
                geoObj: geoObj, // если редактируется существующий объект, передаём его идентификатор
                name: document.getElementById('name').value,
                id_type: document.getElementById('type').value,
                parent_id: document.getElementById('parent_id') ? document.getElementById('parent_id').value : null,
                geometry: geojsonData, // может быть null, если геометрия не обновлялась
            };

            document.getElementById('loader').style.display = 'block';
            document.getElementById('message').style.display = 'none';

            fetch('/admin/promzona-geo-objects/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loader').style.display = 'none';
                    let messageDiv = document.getElementById('message');
                    if (data.success) {
                        document.getElementById('message-text').innerHTML = 'Данные успешно сохранены';
                        messageDiv.style.backgroundColor = '#27ae60';
                    } else {
                        document.getElementById('message-text').innerHTML = 'Ошибка сохранения данных';
                        messageDiv.style.backgroundColor = '#c0392b';
                    }
                    messageDiv.style.display = 'block';
                    setTimeout(() => { messageDiv.style.display = 'none'; }, 2000);
                })
                .catch(error => {
                    document.getElementById('loader').style.display = 'none';
                    let messageDiv = document.getElementById('message');
                    document.getElementById('message-text').innerHTML = 'Ошибка: ' + error;
                    messageDiv.style.backgroundColor = '#c0392b';
                    messageDiv.style.display = 'block';
                    setTimeout(() => { messageDiv.style.display = 'none'; }, 2000);
                    console.error('Ошибка:', error);
                });
        });
        map.on('draw:edited', function(e) {
            e.layers.eachLayer(function(layer) {
                drawnItems.addLayer(layer);
            });
        });
        map.on('draw:deleted', function() {
            marker = null;
            polygon = null;
        });

        document.getElementById('parent_id').addEventListener('change', function() {
            const parentId = this.value;

            // Если значение выбрано, делаем запрос за дочерними объектами
            if (parentId) {
                fetch(`/admin/promzona-geo-objects/children?parent_id=${parentId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Удаляем предыдущий селект для дочерних объектов, если есть
                        let nextSelect = document.getElementById('child_select');
                        if (nextSelect) nextSelect.remove();

                        // Если получены дочерние объекты, создаём новый селект
                        if (data && Object.keys(data).length > 0) {
                            let select = document.createElement('select');
                            select.id = 'child_select';
                            select.name = 'child_id';

                            let defaultOption = document.createElement('option');
                            defaultOption.value = '';
                            defaultOption.text = 'Выберите дочерний объект';
                            select.appendChild(defaultOption);

                            for (const id in data) {
                                let option = document.createElement('option');
                                option.value = id;
                                option.text = data[id];
                                select.appendChild(option);
                            }
                            // Добавляем новый селект после родительского
                            document.getElementById('parent_id').parentNode.appendChild(select);
                        }
                    })
                    .catch(error => console.error('Ошибка загрузки дочерних объектов:', error));
            } else {
                // Если родительский объект не выбран – удаляем селект дочерних объектов
                let nextSelect = document.getElementById('child_select');
                if (nextSelect) nextSelect.remove();
            }
        });
    }, 1000);
</script>
</body>
</html>
