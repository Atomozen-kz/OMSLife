<?php

// Простая проверка что API роуты работают
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Route;

echo "Проверка API роутов для материальной помощи:\n";
echo str_repeat('=', 50) . "\n";

$routes = Route::getRoutes();
$found = false;

foreach ($routes as $route) {
    if (str_contains($route->uri(), 'financial-assistance')) {
        $found = true;
        echo sprintf(
            "%-8s %s\n",
            implode('|', $route->methods()),
            $route->uri()
        );
    }
}

if (!$found) {
    echo "❌ API роуты для материальной помощи не найдены!\n";
    echo "Проверьте файл routes/api.php\n";
} else {
    echo "\n✅ API роуты успешно зарегистрированы!\n";
}

echo str_repeat('=', 50) . "\n";

// Удаляем временный файл
unlink(__FILE__);
