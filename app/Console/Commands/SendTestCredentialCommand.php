<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\PushSotrudnikam;
use App\Models\Sotrudniki;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendTestCredentialCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:test {sotrudnik_id?} {--login=test_user} {--password=Test123!}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестовая отправка учетных данных одному сотруднику';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sotrudnikId = $this->argument('sotrudnik_id') ?? 1372; // ID Иванов Иван Иванович
        $login = $this->option('login');
        $password = $this->option('password');

        $this->info("🧪 ТЕСТОВАЯ ОТПРАВКА УЧЕТНЫХ ДАННЫХ");
        $this->newLine();

        // Поиск сотрудника
        $sotrudnik = Sotrudniki::find($sotrudnikId);

        if (!$sotrudnik) {
            $this->error("❌ Сотрудник с ID {$sotrudnikId} не найден!");
            $this->newLine();
            $this->comment("💡 Используйте: php artisan credentials:test {sotrudnik_id} --login=test --password=pass123");
            return 1;
        }

        $this->info("✅ Сотрудник найден:");
        $this->table(
            ['Поле', 'Значение'],
            [
                ['ID', $sotrudnik->id],
                ['ФИО', $sotrudnik->full_name],
                ['Должность', $sotrudnik->sotrudnik_dolzhnost_name ?? 'N/A'],
            ]
        );
        $this->newLine();

        $this->info("📝 Данные для отправки:");
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Логин', $login],
                ['Пароль', $password],
            ]
        );
        $this->newLine();

        // Генерация текста пуша
        $pushText = $this->generatePushText($login, $password);

        $this->info("📱 Текст уведомления:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line($pushText);
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        // Подтверждение отправки
        if (!$this->confirm('Отправить это уведомление сотруднику?', true)) {
            $this->warn("⚠️  Отправка отменена");
            return 0;
        }

        try {
            DB::transaction(function () use ($sotrudnik, $pushText, $login, $password) {
                // Создаем запись в push_sotrudnikam
                $push = PushSotrudnikam::create([
                    'lang' => 'kz',
                    'title' => 'Оқу порталына кіру деректері',
                    'mini_description' => $pushText,
                    'body' => '',
                    'sended' => 1,
                    'for_all' => 0,
                    'sender_id' => null,
                    'recipient_id' => $sotrudnik->id,
                    'expiry_date' => Carbon::now()->addDays(60),
                ]);

                $this->info("✅ Push уведомление создано (ID: {$push->id})");

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

                // Отправляем немедленно (без задержки)
                SendPushNotification::dispatch($sotrudnik->id, $messageData);

                $this->info("✅ Push уведомление отправлено в очередь");

                // Логирование
                Log::channel('credentials_send')->info('Тестовая отправка учетных данных', [
                    'push_id' => $push->id,
                    'sotrudnik_id' => $sotrudnik->id,
                    'фио' => $sotrudnik->full_name,
                    'логин' => $login,
                    'test' => true,
                ]);
            });

            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✅ УСПЕШНО ОТПРАВЛЕНО!");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->newLine();
            $this->comment("💡 Проверьте мобильное приложение сотрудника");
            $this->comment("📝 Лог: storage/logs/credentials_send.log");
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Ошибка при отправке: {$e->getMessage()}");

            Log::channel('credentials_send')->error('Ошибка тестовой отправки', [
                'sotrudnik_id' => $sotrudnik->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Генерация текста пуш-уведомления
     */
    private function generatePushText(string $login, string $password): string
    {
        return "https://edu.kmge.kz/ сайтына кіруге арналған логин және құпия сөз\n\nЛогин: {$login}\nПароль: {$password}";
    }
}
