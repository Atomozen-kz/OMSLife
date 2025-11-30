<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialAssistanceType;
use App\Models\FinancialAssistanceRequest;
use App\Models\FinancialAssistanceSigner;
use App\Models\Sotrudniki;
use Carbon\Carbon;

class FinancialAssistanceTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем типы материальной помощи
        $medicalType = FinancialAssistanceType::create([
            'name' => 'Медицинская помощь',
            'description' => 'Материальная помощь на лечение и медицинские услуги',
            'statement_html' => '
<div class="main-content">
    <h2>Прошение о предоставлении материальной помощи на лечение</h2>
    
    <p>В связи с необходимостью получения медицинской помощи, прошу предоставить материальную помощь на:</p>
    
    <div class="form-fields">
        {{form_fields}}
    </div>
    
    <div style="margin-top: 20px;">
        <p><strong>Запрашиваемая сумма:</strong> _________________ тенге</p>
        <p><strong>Медицинское заключение прилагается.</strong></p>
    </div>
</div>',
            'status' => true,
            'sort' => 1,
        ]);

        $familyType = FinancialAssistanceType::create([
            'name' => 'Семейные обстоятельства',
            'description' => 'Материальная помощь в связи с семейными обстоятельствами',
            'statement_html' => '
<div class="main-content">
    <h2>Прошение о предоставлении материальной помощи</h2>
    
    <p>В связи со сложными семейными обстоятельствами, прошу предоставить материальную помощь:</p>
    
    <div class="form-fields">
        {{form_fields}}
    </div>
    
    <div style="margin-top: 20px;">
        <p><strong>Запрашиваемая сумма:</strong> _________________ тенге</p>
        <p><strong>Подтверждающие документы прилагаются.</strong></p>
    </div>
</div>',
            'status' => true,
            'sort' => 2,
        ]);

        // Получаем первого доступного сотрудника или создаем тестового
        $sotrudnik = Sotrudniki::first();
        
        if (!$sotrudnik) {
            $this->command->error('В системе нет сотрудников! Создайте хотя бы одного сотрудника для тестирования.');
            return;
        }

        // Создаем подписантов (используем первого пользователя User для подписантов)
        $adminUser = \App\Models\User::first();
        if (!$adminUser) {
            $this->command->error('В системе нет пользователей User для подписантов! Создайте администратора.');
            return;
        }

        $signer1 = FinancialAssistanceSigner::create([
            'id_user' => $adminUser->id,
            'full_name' => 'Петров Петр Петрович',
            'position' => 'Директор по персоналу',
        ]);

        $signer2 = FinancialAssistanceSigner::create([
            'id_user' => $adminUser->id,
            'full_name' => 'Сидоров Сидор Сидорович',
            'position' => 'Заместитель директора',
        ]);

        // Создаем тестовые заявки
        FinancialAssistanceRequest::create([
            'id_sotrudnik' => $sotrudnik->id,
            'id_type' => $medicalType->id,
            'status' => 1, // На рассмотрении
            'form_data' => [
                'Диагноз' => 'Острый гастрит',
                'Медицинское учреждение' => 'Городская больница №1',
                'Стоимость лечения' => '150,000 тенге',
                'Период лечения' => 'Сентябрь 2024',
            ],
            'submitted_at' => Carbon::now()->subDays(3),
        ]);

        FinancialAssistanceRequest::create([
            'id_sotrudnik' => $sotrudnik->id,
            'id_type' => $familyType->id,
            'id_signer' => $signer1->id,
            'status' => 2, // Одобрено
            'form_data' => [
                'Причина обращения' => 'Рождение ребенка',
                'Дата события' => '15.08.2024',
                'Запрашиваемая сумма' => '100,000 тенге',
            ],
            'comment' => 'Заявка одобрена. Поздравляем с пополнением в семье!',
            'submitted_at' => Carbon::now()->subDays(10),
            'processed_at' => Carbon::now()->subDays(7),
        ]);

        FinancialAssistanceRequest::create([
            'id_sotrudnik' => $sotrudnik->id,
            'id_type' => $medicalType->id,
            'id_signer' => $signer2->id,
            'status' => 3, // Отклонено
            'form_data' => [
                'Диагноз' => 'Косметическая процедура',
                'Медицинское учреждение' => 'Частная клиника',
                'Стоимость лечения' => '500,000 тенге',
            ],
            'comment' => 'Косметические процедуры не покрываются материальной помощью согласно регламенту.',
            'submitted_at' => Carbon::now()->subDays(15),
            'processed_at' => Carbon::now()->subDays(12),
        ]);

        $this->command->info('Тестовые данные для материальной помощи созданы успешно!');
        $this->command->info('Создано:');
        $this->command->info('- 2 типа материальной помощи');
        $this->command->info('- 2 подписанта');
        $this->command->info('- 3 тестовые заявки');
        $this->command->info('Теперь вы можете протестировать функционал на /admin/financial-assistance/requests');
    }
}
