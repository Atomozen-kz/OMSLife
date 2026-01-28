<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Orchid\Platform\Models\Role;

class BrigadeChecklistPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Находим роль admin
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole) {
            // Добавляем разрешение к роли
            $adminRole->addPermission('platform.brigade-checklist');
            $this->command->info('✓ Разрешение "platform.brigade-checklist" успешно добавлено роли admin');
        } else {
            $this->command->warn('⚠ Роль admin не найдена.');
        }
    }
}
