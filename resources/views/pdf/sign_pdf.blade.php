<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подписание документа</title>
    <script src="/js/ncalayer-client.js"></script>
</head>
<body>
<h1>Подписание документа</h1>
<button id="sign-button">Подписать документ</button>

<script>
    // Получаем URL документа из Blade-переменной
    const documentUrl = "{{ $documentUrl }}";

    document.getElementById('sign-button').addEventListener('click', function () {
        connectAndSign();
    });

    async function connectAndSign() {
        const ncalayerClient = new NCALayerClient();

        try {
            await ncalayerClient.connect();
        } catch (error) {
            alert(`Не удалось подключиться к NCALayer: ${error.toString()}`);
            return;
        }

        // Загрузка PDF-документа для подписания
        try {
            const response = await fetch(documentUrl);
            const pdfData = await response.arrayBuffer();

            // Преобразование ArrayBuffer в Base64
            const pdfBase64 = arrayBufferToBase64(pdfData);

            // Подписание документа
            let base64EncodedSignature;

            try {
                base64EncodedSignature = await ncalayerClient.basicsSignCMS(
                    NCALayerClient.basicsStorageAll,          // Используем все доступные хранилища
                    pdfBase64,                                // Наш PDF в Base64
                    NCALayerClient.basicsCMSParamsAttached,   // Тип подписи - отделенная (Detached)
                    NCALayerClient.basicsSignerSignAny        // Любой доступный сертификат для подписи
                );
            } catch (error) {
                if (error.canceledByUser) {
                    alert('Действие отменено пользователем.');
                    return;
                }

                alert(`Ошибка при подписании: ${error.toString()}`);
                return;
            }

            // Отправка подписанного документа на сервер
            const formData = new FormData();
            formData.append('signedPdf', base64EncodedSignature);
            formData.append('documentId', {{ $documentId }});

            const result = await fetch('{{ route('document.sign.post') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await result.json();

            if (data.status === 'success') {
                // alert('Документ успешно подписан!');
                window.location.href = '/spravka-sotrudnikam/?success=podpisana';
            } else {
                alert('Ошибка: ' + data.message);
            }
        } catch (error) {
            console.error('Ошибка при загрузке или подписании документа:', error);
            alert('Произошла ошибка при обработке документа.');
        }
    }

    // Функция для преобразования ArrayBuffer в Base64
    function arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
</script>
</body>
</html>
