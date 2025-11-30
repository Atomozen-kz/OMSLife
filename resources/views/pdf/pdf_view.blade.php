<script src="/js/ncalayer-client.js"></script> <!-- Подключаем ncalayer-client.js -->

<div class="text-center">
    <h1 class="mb-4">Просмотр PDF</h1>
    <div class="mb-4">
        <iframe src="{{ asset(Storage::disk('public')->url($spravka->pdf_path)) }}" width="100%" height="600px" style="border: none;"></iframe>
    </div>
        <div class="row">
            <div class="col-5 btn btn-primary btn-lg" style="margin:15px" id="back-to-edit-spravka" >Назад к редактирование</div>
            <div class="col-5 btn btn-success btn-lg" style="margin:15px" id="sign-document" >Подписать</div>
        </div>

    <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div style="text-align: center; color: white;">
            <div class="spinner" style="margin: 15px auto; border: 4px solid rgba(255, 255, 255, 0.3); border-top: 4px solid #fff; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite;"></div>
            <p style="font-size: 18px;">Идёт подписание документа...</p>
        </div>
    </div>

    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</div>
<script>
    const loadingOverlay = document.getElementById('loading-overlay');
    //* Возврат к редактированию справки *//

    document.getElementById('back-to-edit-spravka').addEventListener('click', async () => {
        // Отправка AJAX-запроса
        fetch('{{ route('spravka.backToEdit') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                spravka_id: {{ $spravka->id }} // ID текущей справки
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // alert('Справка успешно возвращена к редактированию!');
                    window.location.href = '{{ route('platform.pdf-preview', ['spravka' => $spravka->id]) }}';
                } else {
                    alert('Произошла ошибка при возврате справки к редактированию.');
                }
            })
            .catch(error => console.error('Ошибка:', error));
    })


        //* Подписание документа *//
    document.getElementById('sign-document').addEventListener('click', async () => {
        try {
            // Создание экземпляра NCALayerClient
            const client = new NCALayerClient();

            // Подключение к NCALayer
            const version = await client.connect();
            console.log(`Подключено к NCALayer. Версия: ${version}`);

            // Получение PDF файла в Base64
            const pdfBase64 = await fetch('{{ asset(Storage::url($spravka->pdf_path)) }}')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Не удалось загрузить PDF файл.');
                    }
                    return response.blob();
                })
                .then(blob => blob.arrayBuffer())
                .then(buffer => NCALayerClient.arrayBufferToB64(buffer));

            console.log('PDF успешно преобразован в Base64.');


            /**
             * Сертификат любого сотрудника юридического лица для подписания выпущенный боевым УЦ НУЦ.
             */

            signerParam = {
                extKeyUsageOids: ['1.3.6.1.5.5.7.3.4', '1.2.398.3.3.4.1.2'],
                iin: '{{$spravka->signer->iin}}',
                bin: '120240020997'
            }
            // Показать загрузчик
            loadingOverlay.style.display = 'flex';

            // Подписание данных
            const signedData = await client.basicsSignCMS(
                NCALayerClient.basicsStorageAll,                // Все доступные хранилища
                pdfBase64,                                     // PDF данные в Base64
                NCALayerClient.basicsCMSParamsAttached,        // Включить данные в подпись
                // NCALayerClient.basicsSignerAuthAny,       // Параметры сертификата сотрудника
                signerParam,
                'ru'                                           // Язык интерфейса
            );


            console.log('Подпись успешно создана:');

            // Отправка подписанных данных на сервер
            const response = await fetch('/admin/save-signed-pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    signedData,
                    spravkaId: {{ $spravka->id }}
                })
            });

            if (!response.ok) {
                // Попробуем получить текст ответа
                const errorMessage = await response.text();
                console.error('Ошибка при сохранении документа:', errorMessage);

                // Если ответ в формате JSON, используйте JSON.parse
                try {

                    const errorJson = JSON.parse(errorMessage);
                    alert(`Ошибка: ${errorJson.error || 'Неизвестная ошибка'}`);
                } catch {
                    // Если не JSON, просто выведем текст
                    alert(`Ошибка: ${errorMessage}`);
                }

                throw new Error('Ошибка при сохранении подписанного документа.');
            }

            const result = await response.json();
            document.getElementById('loading-overlay').style.display = 'none';
            alert('Документ успешно подписан и сохранён!');
            window.location.href = '/admin/spravka-sotrudnikam/';
            // Перенаправление на другую страницу
            // window.location.href = '/admin/spravka-sotrudnikam';

            console.log('Серверный ответ:', result);
        } catch (error) {
            if (error instanceof NCALayerError) {
                if (error.canceledByUser) {
                    console.warn('Операция отменена пользователем.');
                    alert('Подписание документа отменено.');
                } else {
                    console.error('Ошибка NCALayer:', error.message);
                    alert('Ошибка при подписании: ' + error.message);
                }
            } else {
                console.error('Неизвестная ошибка:', error);
                alert('Произошла неизвестная ошибка. Проверьте консоль для подробностей.');
            }
        } finally {
            // Скрыть загрузчик
            loadingOverlay.style.display = 'none';
        }
    });
</script>
