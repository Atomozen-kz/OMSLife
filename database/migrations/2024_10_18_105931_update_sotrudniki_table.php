<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            // Удаляем столбец fio
            $table->dropColumn('fio');

            // Добавляем новые столбцы для имени, фамилии и отчества
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->string('father_name')->nullable()->after('last_name');

            // Изменяем столбец iin, делая его по умолчанию NULL
            $table->string('iin')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            // Восстанавливаем удалённый столбец fio
            $table->string('fio')->after('id');

            // Удаляем столбцы first_name, last_name и father_name
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('father_name');

            // Возвращаем изменение столбца iin
            $table->string('iin')->nullable(false)->change();
        });
    }
};
