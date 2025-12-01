<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMS Life</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            max-width: 600px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
        }
        .logo {
            max-width: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }
        .description {
            font-size: 1.1rem;
            margin: 20px 0;
            line-height: 1.6;
            color: #4a5568;
        }
        .store-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        .store-buttons a {
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .store-buttons a:hover {
            transform: scale(1.05);
        }
        .store-buttons img {
            height: 50px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <img src="/storage/logo-sqr.png" alt="OMS Life Logo" class="logo">
    <h1>OMS Life</h1>
    <p class="description">
        Добро пожаловать в приложение для сотрудников АО "ОзенМунайСервис"!
        Здесь вы найдете актуальную информацию, полезные инструменты и удобные функции для работы и общения.
    </p>
    <div class="store-buttons">
        <a href="https://play.google.com" target="_blank">
            <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play">
        </a>
        <a href="https://apps.apple.com" target="_blank">
            <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="App Store">
        </a>
        <a href="https://appgallery.cloud.huawei.com" target="_blank">
            <img src="/storage/appgallery.png" alt="AppGallery">
        </a>
    </div>
</div>
</body>
</html>
