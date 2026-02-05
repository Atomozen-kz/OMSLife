<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\PushSotrudnikam;
use App\Models\Sotrudniki;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendEducationCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:send {file?} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправка учетных данных для образовательного портала сотрудникам из JSON файла';

    /**
     * Счетчики для статистики
     */
    private int $total = 0;
    private int $found = 0;
    private int $notFound = 0;
    private int $duplicates = 0;
    private int $sent = 0;
    private int $errors = 0;
    private int $sentCounter = 0;
    private array $dryRunExamples = [];
    private array $csvData = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Увеличиваем лимиты для обработки большого файла
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        $isDryRun = $this->option('dry-run');
        $filePath = $this->argument('file') ?? app_path('Console/Commands/data/users_oms.json');

        // Логируем старт команды
        Log::channel('credentials_send')->info('=== Запуск команды отправки учетных данных ===', [
            'file' => $filePath,
            'dry_run' => $isDryRun,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Очищаем лог незнайденных для новой сессии
        Log::channel('credentials_not_found')->info('=== НОВАЯ СЕССИЯ ОТПРАВКИ УЧЕТНЫХ ДАННЫХ ===', [
            'file' => $filePath,
            'dry_run' => $isDryRun,
            'timestamp' => now()->toDateTimeString(),
        ]);

        $this->info("🚀 Запуск команды отправки учетных данных");
        $this->info("📁 Файл: {$filePath}");
        $this->info("🔧 Режим: " . ($isDryRun ? 'DRY RUN (тестовый)' : 'LIVE (реальная отправка)'));
        $this->newLine();

        // Проверка существования файла
        if (!file_exists($filePath)) {
            $this->error("❌ Файл не найден: {$filePath}");
            Log::channel('credentials_send')->error('Файл не найден', ['file' => $filePath]);
            return 1;
        }

        // Чтение и парсинг JSON
        try {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                throw new \Exception('JSON должен содержать объект с цехами');
            }
        } catch (\Exception $e) {
            $this->error("❌ Ошибка чтения JSON: {$e->getMessage()}");
            Log::channel('credentials_send')->error('Ошибка парсинга JSON', [
                'error' => $e->getMessage(),
                'file' => $filePath,
            ]);
            return 1;
        }

        // Подсчитываем общее количество записей
        foreach ($data as $cehName => $employees) {
            if (is_array($employees)) {
                $this->total += count($employees);
            }
        }

        $cehCount = count($data);
        $this->info("📊 Найдено цехов: {$cehCount}");
        $this->info("📊 Всего записей: {$this->total}");
        $this->newLine();

        Log::channel('credentials_send')->info('Статистика файла', [
            'цехов' => $cehCount,
            'записей' => $this->total,
        ]);

        // Инициализируем CSV данные
        $this->csvData[] = ['Цех', 'ФИО', 'Логин', 'Статус', 'ID сотрудника', 'Причина'];

        // Создаем прогресс-бар
        $progressBar = $this->output->createProgressBar($this->total);
        $progressBar->setFormat('[%bar%] %current%/%max% (%percent:3s%%) | %message%');
        $progressBar->setMessage('Начало обработки...');

        // Обрабатываем каждый цех
        foreach ($data as $cehName => $employees) {
            if (!is_array($employees)) {
                Log::channel('credentials_send')->warning('Пропущен цех с некорректной структурой', [
                    'цех' => $cehName,
                ]);
                continue;
            }

            Log::channel('credentials_send')->info("Обработка цеха: {$cehName}", [
                'сотрудников' => count($employees),
            ]);

            // Обрабатываем каждого сотрудника
            foreach ($employees as $index => $employee) {
                $progressBar->setMessage("Обработка: {$cehName}");

                // Валидация структуры данных
                if (!isset($employee['name']) || !isset($employee['login']) || !isset($employee['password'])) {
                    $this->errors++;
                    Log::channel('credentials_send')->error('Некорректная структура данных', [
                        'цех' => $cehName,
                        'индекс' => $index,
                        'данные' => $employee,
                    ]);
                    $this->csvData[] = [
                        $cehName,
                        $employee['name'] ?? 'N/A',
                        $employee['login'] ?? 'N/A',
                        'Ошибка JSON',
                        '',
                        'Отсутствуют обязательные поля',
                    ];
                    $progressBar->advance();
                    continue;
                }

                $name = $employee['name'];
                $login = $employee['login'];
                $password = $employee['password'];

                // Поиск сотрудника в БД
                $result = $this->findAndProcessEmployee($name, $login, $password, $cehName, $isDryRun);

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Сохраняем CSV отчет
        $csvPath = $this->saveCsvReport();

        // Выводим итоговую статистику
        $this->displayStatistics($isDryRun, $csvPath);

        // Показываем примеры в dry-run режиме
        if ($isDryRun && !empty($this->dryRunExamples)) {
            $this->displayDryRunExamples();
        }

        Log::channel('credentials_send')->info('=== Команда завершена ===', [
            'всего' => $this->total,
            'найдено' => $this->found,
            'не_найдено' => $this->notFound,
            'дубликатов' => $this->duplicates,
            'отправлено' => $this->sent,
            'ошибок' => $this->errors,
            'режим' => $isDryRun ? 'dry-run' : 'live',
            'csv_отчет' => $csvPath,
        ]);

        // Логируем итоги по незнайденным
        if ($this->notFound > 0) {
            Log::channel('credentials_not_found')->info('=== ИТОГО НЕ НАЙДЕНО В БД ===', [
                'количество' => $this->notFound,
                'всего_обработано' => $this->total,
                'процент' => round(($this->notFound / $this->total) * 100, 2) . '%',
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        return 0;
    }

    /**
     * Нормализация ФИО для поиска
     */
    private function normalizeFullName(string $name): string
    {
        // Убираем лишние пробелы
        $name = preg_replace('/\s+/', ' ', trim($name));

        // Заменяем ё на е
        $name = str_replace('ё', 'е', $name);
        $name = str_replace('Ё', 'Е', $name);

        // Приводим к нижнему регистру
        return mb_strtolower($name);
    }

    /**
     * Поиск и обработка сотрудника
     */
    private function findAndProcessEmployee(string $name, string $login, string $password, string $cehName, bool $isDryRun): array
    {
        $normalizedName = $this->normalizeFullName($name);

        // Поиск в БД с нормализацией
        $employees = Sotrudniki::whereRaw(
            "LOWER(TRIM(REPLACE(REPLACE(full_name, 'ё', 'е'), '  ', ' '))) = ?",
            [$normalizedName]
        )->get();

        $employeeCount = $employees->count();

        // Обработка дубликатов
        if ($employeeCount > 1) {
            $this->duplicates++;
            $ids = $employees->pluck('id')->toArray();

            Log::channel('credentials_send')->warning('Найдено несколько сотрудников с одинаковым ФИО', [
                'фио' => $name,
                'количество' => $employeeCount,
                'ids' => $ids,
                'цех' => $cehName,
            ]);

            $this->csvData[] = [
                $cehName,
                $name,
                $login,
                'Дубликат',
                implode(', ', $ids),
                "Найдено {$employeeCount} совпадений",
            ];

            // Добавляем в примеры для dry-run (первые 50)
            if ($isDryRun && count($this->dryRunExamples) < 50) {
                $this->dryRunExamples[] = [
                    'фио' => $name,
                    'логин' => $login,
                    'статус' => "⚠️  Дубликат ({$employeeCount} совпадений)",
                    'текст' => 'Пропущено',
                ];
            }

            return ['status' => 'duplicate', 'count' => $employeeCount];
        }

        // Сотрудник не найден
        if ($employeeCount === 0) {
            $this->notFound++;

            Log::channel('credentials_send')->info('Сотрудник не найден в БД', [
                'фио' => $name,
                'логин' => $login,
                'нормализованное_фио' => $normalizedName,
                'цех' => $cehName,
            ]);

            // Логирование в отдельный файл для незнайденных
            Log::channel('credentials_not_found')->info('', [
                'цех' => $cehName,
                'фио' => $name,
                'логин' => $login,
                'пароль' => $password,
                'нормализованное_фио' => $normalizedName,
                'timestamp' => now()->toDateTimeString(),
            ]);

            $this->csvData[] = [
                $cehName,
                $name,
                $login,
                'Не найден',
                '',
                'Отсутствует в БД',
            ];

            // Добавляем в примеры для dry-run (первые 50)
            if ($isDryRun && count($this->dryRunExamples) < 50) {
                $pushText = $this->generatePushText($login, $password);
                $this->dryRunExamples[] = [
                    'фио' => $name,
                    'логин' => $login,
                    'статус' => '❌ Не найден в БД',
                    'текст' => mb_substr($pushText, 0, 100) . '...',
                ];
            }

            return ['status' => 'not_found'];
        }

        // Сотрудник найден
        $sotrudnik = $employees->first();
        $this->found++;

        Log::channel('credentials_send')->info('Сотрудник найден', [
            'фио' => $name,
            'sotrudnik_id' => $sotrudnik->id,
            'логин' => $login,
            'цех' => $cehName,
        ]);

        $pushText = $this->generatePushText($login, $password);

        // Добавляем в примеры для dry-run (первые 50)
        if ($isDryRun && count($this->dryRunExamples) < 50) {
            $this->dryRunExamples[] = [
                'фио' => $name,
                'логин' => $login,
                'статус' => '✅ Найден (ID: ' . $sotrudnik->id . ')',
                'текст' => mb_substr($pushText, 0, 100) . '...',
            ];
        }

        // Если не dry-run - создаем push и отправляем
        if (!$isDryRun) {
            try {
                DB::transaction(function () use ($sotrudnik, $pushText, $login, $password, $name) {
                    // Создаем запись в push_sotrudnikam
                    $push = PushSotrudnikam::create([
                        'lang' => 'kz',
                        'title' => 'Оқу порталына кіру деректері',
                        'mini_description' => $pushText,
                        'body' => '',
                        'sended' => 1,
                        'for_all' => 0,
                        'sender_id' => 1,
                        'recipient_id' => $sotrudnik->id,
                        'expiry_date' => Carbon::now()->addDays(60),
                    ]);

                    Log::channel('credentials_send')->info('Push уведомление создано', [
                        'push_id' => $push->id,
                        'sotrudnik_id' => $sotrudnik->id,
                        'фио' => $name,
                    ]);

                    // Инкрементируем счетчик отправленных
                    $this->sentCounter++;

                    // Формируем данные для Job
                    $messageData = [
                        'title' => $push->title,
                        'body' => $push->mini_description,
                        'image' => null,
                        'data' => [
                            'page' => '/message',
                            'id' => $push->id,
                        ],
                    ];

                    // Диспатчим Job с задержкой
                    SendPushNotification::dispatch($sotrudnik->id, $messageData)
                        ->delay(now()->addSeconds($this->sentCounter));

                    Log::channel('credentials_send')->info('Job отправлен в очередь', [
                        'sotrudnik_id' => $sotrudnik->id,
                        'задержка_сек' => $this->sentCounter,
                        'push_id' => $push->id,
                    ]);

                    $this->sent++;
                });

                $this->csvData[] = [
                    $cehName,
                    $name,
                    $login,
                    'Успешно отправлен',
                    $sotrudnik->id,
                    "Задержка: {$this->sentCounter}с",
                ];

                return ['status' => 'sent', 'sotrudnik_id' => $sotrudnik->id];

            } catch (\Exception $e) {
                $this->errors++;

                Log::channel('credentials_send')->error('Ошибка при создании push', [
                    'фио' => $name,
                    'sotrudnik_id' => $sotrudnik->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->csvData[] = [
                    $cehName,
                    $name,
                    $login,
                    'Ошибка',
                    $sotrudnik->id,
                    $e->getMessage(),
                ];

                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        } else {
            // В dry-run режиме просто считаем как отправленный
            $this->csvData[] = [
                $cehName,
                $name,
                $login,
                'Найден (dry-run)',
                $sotrudnik->id,
                'Тестовый режим - не отправлено',
            ];

            return ['status' => 'dry_run_found', 'sotrudnik_id' => $sotrudnik->id];
        }
    }

    /**
     * Генерация текста пуш-уведомления
     */
    private function generatePushText(string $login, string $password): string
    {
        return "https://edu.kmge.kz/ сайтына кіруге арналған логин және құпия сөз\n\nЛогин: {$login}\nПароль: {$password}";
    }

    /**
     * Сохранение CSV отчета
     */
    private function saveCsvReport(): string
    {
        $filename = 'credentials_report_' . date('Y-m-d_His') . '.csv';
        $path = storage_path('logs/' . $filename);

        $file = fopen($path, 'w');

        // Добавляем BOM для корректного отображения кириллицы в Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($this->csvData as $row) {
            fputcsv($file, $row, ';');
        }

        fclose($file);

        Log::channel('credentials_send')->info('CSV отчет сохранен', ['путь' => $path]);

        return $path;
    }

    /**
     * Вывод итоговой статистики
     */
    private function displayStatistics(bool $isDryRun, string $csvPath): void
    {
        $this->info('📊 ИТОГОВАЯ СТАТИСТИКА:');
        $this->newLine();

        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Режим', $isDryRun ? '🔧 DRY RUN (тестовый)' : '✅ LIVE (реальная отправка)'],
                ['Всего записей', $this->total],
                ['Найдено в БД', $this->found],
                ['Не найдено в БД', $this->notFound],
                ['Дубликатов (пропущено)', $this->duplicates],
                ['Успешно отправлено', $isDryRun ? 'N/A (dry-run)' : $this->sent],
                ['Ошибок', $this->errors],
            ]
        );

        $this->newLine();
        $this->info("📄 CSV отчет: {$csvPath}");
        $this->info("📝 Лог файл: " . storage_path('logs/credentials_send.log'));
        if ($this->notFound > 0) {
            $this->info("⚠️  Не найдено в БД: " . storage_path('logs/credentials_not_found.log'));
        }
        $this->newLine();
    }

    /**
     * Вывод примеров в dry-run режиме
     */
    private function displayDryRunExamples(): void
    {
        $this->newLine();
        $this->info('🔍 ПРИМЕРЫ (первые 50 сотрудников):');
        $this->newLine();

        $tableData = [];
        foreach ($this->dryRunExamples as $example) {
            $tableData[] = [
                $example['фио'],
                $example['логин'],
                $example['статус'],
                $example['текст'],
            ];
        }

        $this->table(
            ['ФИО', 'Логин', 'Статус поиска', 'Текст пуша (превью)'],
            $tableData
        );

        $this->newLine();
        $this->comment('💡 Это тестовый режим. Для реальной отправки запустите команду без --dry-run');
    }
}
