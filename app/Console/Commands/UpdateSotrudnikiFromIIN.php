<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sotrudniki;
use Carbon\Carbon;

class UpdateSotrudnikiFromIIN extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sotrudniki:update-from-iin {--force : Обновить даже если данные уже заполнены}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет дату рождения и пол сотрудников на основе ИИН';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начинаем обновление данных сотрудников на основе ИИН...');

        $force = $this->option('force');

        // Получаем сотрудников с заполненным ИИН
        $query = Sotrudniki::whereNotNull('iin')->where('iin', '!=', '');

        // Если не указан флаг force, обновляем только записи с пустыми birthdate или gender
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('birthdate')
                  ->orWhereNull('gender')
                  ->orWhere('birthdate', '')
                  ->orWhere('gender', '');
            });
        }

        $sotrudniki = $query->get();
        $total = $sotrudniki->count();

        if ($total === 0) {
            $this->info('Нет сотрудников для обновления.');
            return 0;
        }

        $this->info("Найдено сотрудников для обновления: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($sotrudniki as $sotrudnik) {
            try {
                $iinData = $this->parseIIN($sotrudnik->iin);

                if ($iinData) {
                    $sotrudnik->birthdate = $iinData['birthdate'];
                    $sotrudnik->gender = $iinData['gender'];
                    $sotrudnik->save();
                    $updated++;
                } else {
                    $errors++;
                    $this->newLine();
                    $this->warn("Некорректный ИИН для сотрудника ID {$sotrudnik->id}: {$sotrudnik->iin}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Ошибка при обновлении сотрудника ID {$sotrudnik->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Обновление завершено!");
        $this->info("Успешно обновлено: {$updated}");

        if ($errors > 0) {
            $this->warn("Ошибок: {$errors}");
        }

        return 0;
    }

    /**
     * Парсит ИИН и возвращает дату рождения и пол
     *
     * @param string $iin
     * @return array|null
     */
    private function parseIIN($iin)
    {
        // Убираем пробелы и проверяем длину
        $iin = preg_replace('/\s+/', '', $iin);

        if (strlen($iin) !== 12 || !is_numeric($iin)) {
            return null;
        }

        // Первые 6 цифр - дата рождения (YYMMDD)
        $year = substr($iin, 0, 2);
        $month = substr($iin, 2, 2);
        $day = substr($iin, 4, 2);

        // 7-я цифра - век и пол
        $centuryGender = (int)substr($iin, 6, 1);

        // Определяем век и пол
        $century = null;
        $gender = null;

        switch ($centuryGender) {
            case 1:
                $century = 1800;
                $gender = 'male';
                break;
            case 2:
                $century = 1800;
                $gender = 'female';
                break;
            case 3:
                $century = 1900;
                $gender = 'male';
                break;
            case 4:
                $century = 1900;
                $gender = 'female';
                break;
            case 5:
                $century = 2000;
                $gender = 'male';
                break;
            case 6:
                $century = 2000;
                $gender = 'female';
                break;
            default:
                return null;
        }

        // Формируем полную дату рождения
        $fullYear = $century + (int)$year;

        try {
            // Проверяем корректность даты
            $birthdate = Carbon::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $fullYear, $month, $day));

            return [
                'birthdate' => $birthdate->format('Y-m-d'),
                'gender' => $gender
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
