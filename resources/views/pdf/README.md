# Шаблоны PDF для материальной помощи

## Структура

Система PDF для материальной помощи теперь разделена на три компонента:

### 1. Header (`financial-assistance-header.blade.php`)
- Содержит заголовок документа, информацию об организации
- Данные заявителя и тип материальной помощи
- Автоматически генерируется

### 2. Content (хранится в БД)
- Центральная часть документа
- Редактируется через админ-панель
- Содержит основное содержимое заявления
- Поддерживает плейсхолдеры

### 3. Footer (`financial-assistance-footer.blade.php`)
- Подписи заявителя и руководителя
- Дата рассмотрения
- Служебная информация
- Автоматически генерируется

## Использование

### В контроллере

```php
use App\Services\FinancialAssistancePdfService;

$pdfService = new FinancialAssistancePdfService();

// Генерация полного HTML для существующей заявки
$fullHtml = $pdfService->generateFullHtmlDocument($request, $sotrudnik, $signer);

// Генерация HTML по типу материальной помощи
$fullHtml = $pdfService->generateHtmlByType($type, $sotrudnik, $signer, $formData);
```

### Через модель

```php
// Получить полный HTML документ
$fullHtml = $assistanceType->getFullHtmlDocument($request, $sotrudnik, $signer);
```

## Плейсхолдеры

### Базовые плейсхолдеры:
- `{{current_date}}` - текущая дата
- `{{current_datetime}}` - текущие дата и время
- `{{form_fields}}` - автоматически генерируемые поля формы

### Плейсхолдеры сотрудника:
- `{{sotrudnik.full_name}}` - ФИО сотрудника
- `{{sotrudnik.position}}` - должность
- `{{sotrudnik.department}}` - подразделение

### Динамические плейсхолдеры:
- Создаются автоматически для каждого поля типа материальной помощи
- Формат: `{{имя_поля_в_нижнем_регистре}}`

## Данные для шаблонов

### Header данные:
```php
[
    'sotrudnik' => User,
    'assistance_type' => FinancialAssistanceType,
    'current_date' => string,
    'department' => string,
    'request_id' => int
]
```

### Footer данные:
```php
[
    'sotrudnik' => User,
    'signer' => FinancialAssistanceSigner,
    'current_date' => string,
    'processed_date' => string|null,
    'request_id' => int|null
]
```

## Стили

Стили определены в header файле и включают:
- Основные стили документа
- Стили для форм и таблиц
- Стили для печати
- Адаптивные стили

## Примеры использования

### 1. Полный документ
```php
@include('pdf.financial-assistance-full', [
    'sotrudnik' => $user,
    'assistanceType' => $type,
    'request' => $request,
    'signer' => $signer,
    'contentHtml' => $processedContent
])
```

### 2. Только header
```php
@include('pdf.financial-assistance-header', [
    'sotrudnik' => $user,
    'assistance_type' => $type,
    'current_date' => date('d.m.Y')
])
```

### 3. Только footer
```php
@include('pdf.financial-assistance-footer', [
    'sotrudnik' => $user,
    'signer' => $signer,
    'current_date' => date('d.m.Y')
])
```
