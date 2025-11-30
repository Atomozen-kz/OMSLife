<?php

/**
 * Простой тестовый скрипт для проверки API материальной помощи
 * 
 * Использование:
 * php tests/api_test_financial_assistance.php
 * 
 * Перед запуском:
 * 1. Убедитесь что есть тестовые данные (php artisan db:seed --class=FinancialAssistanceTestDataSeeder)
 * 2. Получите токен авторизации для тестового пользователя
 * 3. Измените переменные ниже
 */

// Конфигурация
$baseUrl = 'http://127.0.0.1:8000/api/financial-assistance';
$token = 'YOUR_TOKEN_HERE'; // Замените на реальный токен

// Функция для отправки HTTP запросов
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'response' => json_decode($response, true),
        'http_code' => $httpCode,
        'raw_response' => $response
    ];
}

// Функция для красивого вывода результатов
function printResult($testName, $result) {
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "ТЕСТ: $testName\n";
    echo str_repeat('=', 50) . "\n";
    
    if (isset($result['error'])) {
        echo "❌ ОШИБКА: " . $result['error'] . "\n";
        return;
    }
    
    echo "HTTP код: " . $result['http_code'] . "\n";
    
    if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
        echo "✅ УСПЕШНО\n";
    } else {
        echo "❌ ОШИБКА\n";
    }
    
    echo "Ответ:\n";
    echo json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "🚀 Начинаем тестирование API материальной помощи...\n";
echo "Base URL: $baseUrl\n";

// Проверка токена
if ($token === 'YOUR_TOKEN_HERE') {
    echo "❌ ОШИБКА: Пожалуйста, установите реальный токен в переменной \$token\n";
    echo "Получить токен можно через:\n";
    echo "1. Авторизацию в API\n";
    echo "2. Или через tinker: User::find(1)->createToken('test')->plainTextToken\n";
    exit(1);
}

// Тест 1: Получение списка типов материальной помощи
$result = makeRequest($baseUrl . '/types', 'GET', null, $token);
printResult('Получение списка типов материальной помощи', $result);

// Сохраняем ID первого типа для следующих тестов
$typeId = null;
if (isset($result['response']['data'][0]['id'])) {
    $typeId = $result['response']['data'][0]['id'];
}

// Тест 2: Получение деталей типа материальной помощи
if ($typeId) {
    $result = makeRequest($baseUrl . '/types/' . $typeId, 'GET', null, $token);
    printResult('Получение деталей типа материальной помощи', $result);
} else {
    echo "\n❌ Пропускаем тест деталей типа - не найден ID типа\n";
}

// Тест 3: Подача заявки
if ($typeId) {
    $requestData = [
        'type_id' => $typeId,
        'form_data' => [
            'Диагноз' => 'Тестовый диагноз через API',
            'Медицинское учреждение' => 'Тестовая больница',
            'Стоимость лечения' => '100,000 тенге',
            'Период лечения' => 'Сентябрь 2024'
        ]
    ];
    
    $result = makeRequest($baseUrl . '/requests', 'POST', $requestData, $token);
    printResult('Подача заявки на материальную помощь', $result);
    
    // Сохраняем ID заявки для следующих тестов
    $requestId = null;
    if (isset($result['response']['data']['request_id'])) {
        $requestId = $result['response']['data']['request_id'];
    }
} else {
    echo "\n❌ Пропускаем тест подачи заявки - не найден ID типа\n";
}

// Тест 4: Получение списка заявок пользователя
$result = makeRequest($baseUrl . '/requests', 'GET', null, $token);
printResult('Получение списка заявок пользователя', $result);

// Если не получили ID из предыдущего теста, попробуем взять из списка
if (!$requestId && isset($result['response']['data'][0]['id'])) {
    $requestId = $result['response']['data'][0]['id'];
}

// Тест 5: Получение деталей заявки
if ($requestId) {
    $result = makeRequest($baseUrl . '/requests/' . $requestId, 'GET', null, $token);
    printResult('Получение деталей заявки', $result);
} else {
    echo "\n❌ Пропускаем тест деталей заявки - не найден ID заявки\n";
}

// Тест 6: Тест с неверными данными (валидация)
if ($typeId) {
    $invalidRequestData = [
        'type_id' => $typeId,
        'form_data' => [] // Пустые данные для проверки валидации
    ];
    
    $result = makeRequest($baseUrl . '/requests', 'POST', $invalidRequestData, $token);
    printResult('Тест валидации (ожидается ошибка)', $result);
}

// Тест 7: Тест без авторизации
$result = makeRequest($baseUrl . '/types', 'GET', null, null);
printResult('Тест без авторизации (ожидается ошибка 401)', $result);

echo "\n" . str_repeat('=', 50) . "\n";
echo "🏁 Тестирование завершено!\n";
echo str_repeat('=', 50) . "\n";

echo "\nИнструкции по получению токена:\n";
echo "1. Через tinker: php artisan tinker\n";
echo "2. Выполнить: User::find(1)->createToken('test')->plainTextToken\n";
echo "3. Скопировать полученный токен в переменную \$token\n";
echo "\nИли создать тестового пользователя:\n";
echo "php artisan db:seed --class=FinancialAssistanceTestDataSeeder\n";
