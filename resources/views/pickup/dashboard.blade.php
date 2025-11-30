@extends('pickup.layout')

@section('title', 'Личный кабинет пункта выдачи')

@section('content')
    <div class="dashboard-container">
        <h2>Здравствуйте, {{ $pickup->name }}</h2>

        <!-- Адрес + ссылка на Яндекс.Карты -->
        <div class="address-block" style="padding-bottom: 2rem">
            <p>Адрес: {{ $pickup->address }}</p>
            @php
                // Формируем ссылку: https://yandex.ru/maps/?pt=lng,lat&z=15&l=map
                $yandexMapLink = "https://yandex.ru/maps/?pt={$pickup->lng},{$pickup->lat}&z=15&l=map";
            @endphp
            <p>
                <a href="{{ $yandexMapLink }}" target="_blank" rel="noopener noreferrer">
                    Показать в Яндекс.Картах
                </a>
            </p>
        </div>

        <div class="open-status-block">
            <!-- Одна строка: [«Сейчас открыто:» | toggle ] -->
            <div class="open-status-row">
                <span class="status-label">{{ $pickup->is_open ? 'Открыто' : 'Закрыто' }}</span>

                <!-- Тумблер (toggle switch) -->
                <label class="switch">
                    <input
                        type="checkbox"
                        name="is_open"
                        id="isOpenToggle"
                        {{ $pickup->is_open ? 'checked' : '' }}
                    >
                    <span class="slider round"></span>
                </label>

                <!-- Loader (скрытый по умолчанию) -->
                <div id="loader" class="loader" style="display: none;"></div>
            </div>
        </div>
        @php
            $statusText = '';
            $statusClass = '';

            switch ($pickup->quantity) {
                case 1:
                    $statusText = 'Очень мало';
                    $statusClass = 'status-very-low';
                    break;
                case 2:
                    $statusText = 'Мало';
                    $statusClass = 'status-low';
                    break;
                case 3:
                    $statusText = 'В наличии';
                    $statusClass = 'status-medium';
                    break;
                case 4:
                    $statusText = 'Много';
                    $statusClass = 'status-high';
                    break;
                case 5:
                    $statusText = 'Очень много';
                    $statusClass = 'status-very-high';
                    break;
                default:
                    $statusText = 'Нет данных';
                    $statusClass = 'status-none';
                    break;
            }
        @endphp

        <div class="status-container">
            <span class="status-label">Наличие:</span>
            <div class="status-buttons">
                @php
                    $statuses = [
                        0 => 'Нет в наличии',
                        1 => 'Очень мало',
                        2 => 'Мало',
                        3 => 'Средне',
                        4 => 'В наличии',
                        5 => 'Много',
                    ];
                @endphp

                @for ($i = 0; $i <= 5; $i++)
                    <button
                        class="status-button {{ ($i > 0 && $i <= $pickup->quantity) ? 'active' : '' }}"
                        data-status="{{ $i }}"
                        onclick="updateStatus({{ $i }})"
                    >
                        {{ $statuses[$i] }}
                    </button>
                @endfor
            </div>
            <div id="loaderStatus" class="loader" style="display: none;"></div>
        </div>
    </div>

    <!-- JS для AJAX при переключении -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isOpenToggle = document.getElementById('isOpenToggle');
            const loader = document.getElementById('loader');
            const statusLabel = document.querySelector('.status-label'); // Получаем элемент для изменения текста

            isOpenToggle.addEventListener('change', function() {
                // Показать loader
                loader.style.display = 'inline-block';

                // Собираем данные
                const isOpenValue = isOpenToggle.checked ? 1 : 0;

                // Отправляем AJAX (fetch) на маршрут updateIsOpen
                fetch("{{ route('pickup.updateIsOpen') }}", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        is_open: isOpenValue
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        // Скрываем loader
                        loader.style.display = 'none';

                        // Обновляем текст статус-лейбла
                        if (isOpenValue) {
                            statusLabel.textContent = 'Открыто';
                        } else {
                            statusLabel.textContent = 'Закрыто';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        loader.style.display = 'none';
                        alert('Произошла ошибка при обновлении статуса');
                    });
            });
        });
    </script>

    <script>
        function updateStatus(quantity) {
            const loaderStatus = document.getElementById('loaderStatus');
            loaderStatus.style.display = 'inline-block';
            fetch("{{ route('pickup.updateStatus') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    quantity: quantity
                })
            })
                .then(response => response.json())
                .then(data => {
                    loaderStatus.style.display = 'none';
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Не удалось обновить статус');
                    }
                })
                .catch(err => {
                    loaderStatus.style.display = 'none';
                    console.error(err);
                    alert('Произошла ошибка');
                });
        }
    </script>
@endsection
