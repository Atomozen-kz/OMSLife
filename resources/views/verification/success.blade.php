<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка документа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            color: #4CAF50;
            text-align: center;
        }
        p {
            font-size: 16px;
            margin: 10px 0;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
        }
        .download-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        .download-btn a {
            display: inline-block;
            text-decoration: none;
            background: #4CAF50;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }
        .download-btn a:hover {
            background: #45a049;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Документ успешно проверен</h1>
    <p><strong>Автор:</strong> {{ $spravka->signer->last_name }} {{ $spravka->signer->first_name }} {{ $spravka->signer->father_name }}</p>
    <p><strong>Должность:</strong> {{ $spravka->signer->position }}</p>
    <p><strong>Дата подписания:</strong> {{ $spravka->updated_at->format('d.m.Y H:i:s') }}</p>
    <p><strong>ID документа:</strong> {{ $spravka->id }}</p>
    <p class="status"><strong>Статус:</strong> {{ $spravka->status == 7 ? 'Активен' : 'Неактивен' }}</p>

    <div class="download-btn">
        <a style="    margin-bottom: 15px;" href="{{ asset('storage/' . $spravka->ddc_path) }}" download>Скачать карточку документ</a><br>
        <a style="    margin-bottom: 15px;" href="{{ asset('storage/' . $spravka->pdf_path) }}" download>Скачать оригинал документ</a><br>
        <a href="{{ asset('storage/' . $spravka->signed_path) }}" download>Скачать подписанный документ</a>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} OMG Life. Все права защищены.</p>
    </div>
</div>
</body>
</html>
