<?php

namespace App\Console\Commands;

use App\Models\RemontBrigadeData;
use App\Models\RemontBrigadesPlan;
use Illuminate\Console\Command;

class CopyRemontBrigadesDataToPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remont:copy-data-to-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Копирование данных из remont_brigades_data в remont_brigades_plan (месяц и план)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Начинаем копирование данных...');

        $data = RemontBrigadeData::all();
        $copied = 0;
        $skipped = 0;

        foreach ($data as $item) {
            // Проверяем, существует ли уже запись с таким brigade_id и month
            $exists = RemontBrigadesPlan::where('brigade_id', $item->brigade_id)
                ->where('month', $item->month_year)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            RemontBrigadesPlan::create([
                'brigade_id' => $item->brigade_id,
                'month' => $item->month_year,
                'plan' => $item->plan,
            ]);

            $copied++;
        }

        $this->info("Копирование завершено!");
        $this->info("Скопировано: {$copied}");
        $this->info("Пропущено (уже существуют): {$skipped}");

        return Command::SUCCESS;
    }
}

