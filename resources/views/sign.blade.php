<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подписание PDF через NCALayer</title>
    <script src="/js/ncalayer-client.js"></script> <!-- Подключаем ncalayer-client.js -->
</head>
<body>
<h2>Подписание PDF через NCALayer</h2>
<button id="signButton">Подписать документ</button>
<p id="hashOutput"></p>
<p id="signatureOutput"></p>
<p id="resultOutput"></p>

<script>
    document.getElementById('signButton').addEventListener('click', async function() {
        const documentPath = "{{ $documentUrl }}"; // Путь к документу для регистрации
        const documentIdServer = "{{ $documentId }}"; // Путь к документу для регистрации
        try {
            // Шаг 1: Регистрация документа в SIGEX
            const registerDocResponse = await fetch('/api/sign/register-document', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    document_path: documentPath,
                    document_id: documentIdServer,
                    description: 'Подписание документа через NCALayer',
                }),
            });

            const registerDocData = await registerDocResponse.json();

            if (!registerDocResponse.ok) {
                throw new Error(registerDocData.error);
            }

            const documentId = registerDocData.document_id;
            document.getElementById('resultOutput').innerText = 'Документ зарегистрирован в SIGEX. ID: ' + documentId;

            // Шаг 2: Получение хеша
            const hashResponse = await fetch(`/api/sign/get-hash/${documentId}`);
            const hashData = await hashResponse.json();

            if (hashResponse.ok) {
                document.getElementById('hashOutput').innerText = 'Хеш: ' + hashData.hash;
            } else {
                throw new Error(hashData.error);
            }

            // Шаг 3: Подписание хеша через NCALayer
            const ncalayer = new NCALayerClient();
            await ncalayer.connect();

            const signature = await ncalayer.basicsSignCMS(
                NCALayerClient.basicsStorageAll,            // Используем все доступные хранилища
                hashData.hash,                              // Хеш для подписи
                NCALayerClient.basicsCMSParamsDetachedHash, // Параметры для подписи хеша
                NCALayerClient.basicsSignerSignAny          // Любой сертификат для подписи
            );

            document.getElementById('signatureOutput').innerText = 'Подпись: ' + signature;

            // Шаг 4: Отправка подписи на сервер для регистрации
            const registerSigResponse = await fetch('/api/sign/register-signature', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    document_id: documentId,
                    signature: signature,
                }),
            });

            const registerSigData = await registerSigResponse.json();

            if (registerSigResponse.ok) {
                document.getElementById('resultOutput').innerText += '\n' + registerSigData.message;
            } else {
                throw new Error(registerSigData.error);
            }

            // Шаг 5: Формирование карточки документа
            const cardResponse = await fetch('/api/sign/build-document-card', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    document_id: documentId,
                    file_name: 'document_card.pdf',
                }),
            });

            const cardData = await cardResponse.json();

            if (cardResponse.ok) {
                document.getElementById('resultOutput').innerText += '\n' + cardData.message;
            } else {
                throw new Error(cardData.error);
            }

        } catch (error) {
            console.error('Ошибка:', error);
            document.getElementById('resultOutput').innerText = 'Ошибка: ' + error.message;
        }
    });
</script>
</body>
</html>
