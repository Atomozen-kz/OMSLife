<?php

/**
 * Тестовый скрипт для проверки новой системы токенов
 *
 * Использование:
 * php tests/test_token_system.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Загружаем Laravel приложение
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Тестирование системы токенов ===\n\n";

// Конфигурация
$baseUrl = 'http://localhost:8000/api'; // Измените на ваш URL
$testPhone = '+77089222820'; // Тестовый номер
$testCode = '1234';

echo "Base URL: $baseUrl\n";
echo "Test Phone: $testPhone\n\n";

// Функция для выполнения HTTP запросов
function makeRequest($url, $method = 'POST', $data = [], $headers = []) {
    $ch = curl_init();

    $defaultHeaders = ['Content-Type: application/json'];
    $allHeaders = array_merge($defaultHeaders, $headers);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Тест 1: Регистрация (шаг 1)
echo "📝 Тест 1: Отправка SMS кода\n";
echo str_repeat('-', 50) . "\n";

$registerResponse = makeRequest("$baseUrl/verify-sms", 'POST', [
    'phone_number' => $testPhone,
    'code' => $testCode
]);

if ($registerResponse['code'] === 200) {
    echo "✅ Успешно! SMS код отправлен\n";
    echo "Response: " . json_encode($registerResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
} else {
    echo "❌ Ошибка! HTTP Code: " . $registerResponse['code'] . "\n";
    echo "Response: " . json_encode($registerResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// Тест 2: Верификация SMS и получение токенов
echo "🔐 Тест 2: Верификация SMS и получение токенов\n";
echo str_repeat('-', 50) . "\n";

$verifyResponse = makeRequest("$baseUrl/verify-sms", 'POST', [
    'phone_number' => $testPhone,
    'code' => $testCode
]);

$accessToken = null;
$refreshToken = null;

if ($verifyResponse['code'] === 200 && isset($verifyResponse['body']['access_token'])) {
    $accessToken = $verifyResponse['body']['access_token'];
    $refreshToken = $verifyResponse['body']['refresh_token'];

    echo "✅ Успешно! Токены получены\n";
    echo "Access Token: " . substr($accessToken, 0, 20) . "...\n";
    echo "Refresh Token: " . substr($refreshToken, 0, 20) . "...\n";
    echo "Expires At: " . $verifyResponse['body']['expires_at'] . "\n\n";
} else {
    echo "❌ Ошибка! Не удалось получить токены\n";
    echo "Response: " . json_encode($verifyResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    exit(1);
}

// Тест 3: Использование access токена
echo "🔑 Тест 3: Использование access токена\n";
echo str_repeat('-', 50) . "\n";

$detailsResponse = makeRequest("$baseUrl/getSotrudnikDetails", 'GET', [], [
    "Authorization: Bearer $accessToken"
]);

if ($detailsResponse['code'] === 200) {
    echo "✅ Успешно! Данные получены\n";
    echo "Сотрудник: " . ($detailsResponse['body']['full_name'] ?? 'N/A') . "\n\n";
} else {
    echo "❌ Ошибка! HTTP Code: " . $detailsResponse['code'] . "\n";
    echo "Response: " . json_encode($detailsResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// Тест 4: Обновление токена через refresh token
echo "🔄 Тест 4: Обновление токенов\n";
echo str_repeat('-', 50) . "\n";

$refreshResponse = makeRequest("$baseUrl/refresh-token", 'POST', [
    'refresh_token' => $refreshToken
]);

$newAccessToken = null;

if ($refreshResponse['code'] === 200 && isset($refreshResponse['body']['access_token'])) {
    $newAccessToken = $refreshResponse['body']['access_token'];
    $newRefreshToken = $refreshResponse['body']['refresh_token'];

    echo "✅ Успешно! Токены обновлены\n";
    echo "New Access Token: " . substr($newAccessToken, 0, 20) . "...\n";
    echo "New Refresh Token: " . substr($newRefreshToken, 0, 20) . "...\n\n";
} else {
    echo "❌ Ошибка! Не удалось обновить токены\n";
    echo "Response: " . json_encode($refreshResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// Тест 5: Проверка что старый токен не работает
echo "🚫 Тест 5: Проверка инвалидации старого токена\n";
echo str_repeat('-', 50) . "\n";

$oldTokenResponse = makeRequest("$baseUrl/getSotrudnikDetails", 'GET', [], [
    "Authorization: Bearer $accessToken"
]);

if ($oldTokenResponse['code'] === 401) {
    echo "✅ Успешно! Старый токен корректно инвалидирован\n";
    echo "Message: " . ($oldTokenResponse['body']['message'] ?? 'N/A') . "\n\n";
} else {
    echo "⚠️ Предупреждение! Старый токен все еще работает\n";
    echo "HTTP Code: " . $oldTokenResponse['code'] . "\n\n";
}

// Тест 6: Использование нового токена
echo "✨ Тест 6: Использование нового токена\n";
echo str_repeat('-', 50) . "\n";

$newTokenResponse = makeRequest("$baseUrl/getSotrudnikDetails", 'GET', [], [
    "Authorization: Bearer $newAccessToken"
]);

if ($newTokenResponse['code'] === 200) {
    echo "✅ Успешно! Новый токен работает\n\n";
} else {
    echo "❌ Ошибка! Новый токен не работает\n";
    echo "Response: " . json_encode($newTokenResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// Тест 7: Повторная авторизация (симуляция входа с другого устройства)
echo "📱 Тест 7: Вход с другого устройства\n";
echo str_repeat('-', 50) . "\n";

$secondDeviceResponse = makeRequest("$baseUrl/verify-sms", 'POST', [
    'phone_number' => $testPhone,
    'code' => $testCode
]);

$secondDeviceToken = null;

if ($secondDeviceResponse['code'] === 200 && isset($secondDeviceResponse['body']['access_token'])) {
    $secondDeviceToken = $secondDeviceResponse['body']['access_token'];
    echo "✅ Успешно! Второе устройство авторизовано\n\n";
} else {
    echo "❌ Ошибка! Не удалось авторизоваться с второго устройства\n\n";
}

// Тест 8: Проверка что токен первого устройства не работает
echo "🔒 Тест 8: Проверка инвалидации токена первого устройства\n";
echo str_repeat('-', 50) . "\n";

$firstDeviceResponse = makeRequest("$baseUrl/getSotrudnikDetails", 'GET', [], [
    "Authorization: Bearer $newAccessToken"
]);

if ($firstDeviceResponse['code'] === 401) {
    echo "✅ Успешно! Токен первого устройства инвалидирован после входа со второго\n\n";
} else {
    echo "⚠️ Предупреждение! Токен первого устройства все еще работает\n";
    echo "HTTP Code: " . $firstDeviceResponse['code'] . "\n\n";
}

// Тест 9: Logout
echo "👋 Тест 9: Выход из аккаунта\n";
echo str_repeat('-', 50) . "\n";

$logoutResponse = makeRequest("$baseUrl/logout", 'POST', [], [
    "Authorization: Bearer $secondDeviceToken"
]);

if ($logoutResponse['code'] === 200) {
    echo "✅ Успешно! Выход выполнен\n\n";
} else {
    echo "❌ Ошибка при выходе\n";
    echo "Response: " . json_encode($logoutResponse['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// Тест 10: Проверка что токен не работает после logout
echo "🔐 Тест 10: Проверка токена после logout\n";
echo str_repeat('-', 50) . "\n";

$afterLogoutResponse = makeRequest("$baseUrl/getSotrudnikDetails", 'GET', [], [
    "Authorization: Bearer $secondDeviceToken"
]);

if ($afterLogoutResponse['code'] === 401) {
    echo "✅ Успешно! Токен инвалидирован после logout\n\n";
} else {
    echo "⚠️ Предупреждение! Токен все еще работает после logout\n";
    echo "HTTP Code: " . $afterLogoutResponse['code'] . "\n\n";
}

// Проверка БД
echo "💾 Проверка базы данных\n";
echo str_repeat('-', 50) . "\n";

try {
    $sotrudnik = DB::table('sotrudniki')
        ->where('phone_number', $testPhone)
        ->first(['access_token', 'refresh_token', 'token_expires_at']);

    if ($sotrudnik) {
        if ($sotrudnik->access_token === null && $sotrudnik->refresh_token === null) {
            echo "✅ Токены корректно удалены из БД после logout\n";
        } else {
            echo "⚠️ Токены все еще присутствуют в БД:\n";
            echo "  - Access Token: " . ($sotrudnik->access_token ? 'EXISTS' : 'NULL') . "\n";
            echo "  - Refresh Token: " . ($sotrudnik->refresh_token ? 'EXISTS' : 'NULL') . "\n";
        }
    } else {
        echo "❌ Сотрудник не найден в БД\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка при проверке БД: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Тестирование завершено!\n";

