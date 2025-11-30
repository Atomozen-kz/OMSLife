<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMG Life</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 800px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            border-radius: 8px;
        }
        .logo {
            max-width: 200px;
            margin: 0 auto 20px;
            border-radius: 50px;
            border: 1px solid #00000042;
        }
        .description {
            font-size: 1.2rem;
            margin: 20px 0;
            line-height: 1.6;
        }
        .store-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .store-buttons a {
            display: inline-block;
            height: 50px;

        }
        .store-buttons img {
            height: 50px;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        .store-buttons img:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
<div class="container">
    <img src="/storage/omg-logo-11.jpg" alt="OMG Life Logo" class="logo">
    <p class="description">
        Добро пожаловать в OMG Life — приложение для сотрудников АО "ОзенМунайГаз"!
        Здесь вы найдете актуальную информацию, полезные инструменты и удобные функции для работы и общения.
    </p>
    <div class="store-buttons">
        <a href="https://play.google.com/store/apps/details?id=kz.omglife.ozenmunaigaslife" target="_blank">
            <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play">
        </a>
        <a href="https://apps.apple.com/kz/app/omg-life-%D0%B0%D0%BE-%D0%BE%D0%B7%D0%B5%D0%BD%D0%BC%D1%83%D0%BD%D0%B0%D0%B9%D0%B3%D0%B0%D0%B7/id6739495373" target="_blank">
            <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="App Store">
        </a>
        <a href="https://appgallery.cloud.huawei.com/ag/n/app/C113499579" target="_blank">
            <img src="/storage/appgallery.png" alt="AppGallery">
        </a>
    </div>
</div>
</body>
</html>
