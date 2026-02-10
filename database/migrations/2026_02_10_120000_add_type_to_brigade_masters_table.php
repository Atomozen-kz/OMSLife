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
        Schema::table('brigade_masters', function (Blueprint $table) {
            // Добавляем поле type с default значением 'brigade'
            $table->enum('type', ['brigade', 'workshop'])
                ->default('brigade')
                ->after('sotrudnik_id')
                ->comment('Тип мастера: brigade - мастер бригады, workshop - мастер цеха');
        });

        // Удаляем уникальный индекс с sotrudnik_id отдельным запросом
        Schema::table('brigade_masters', function (Blueprint $table) {
            $table->dropUnique(['sotrudnik_id']);
        });

        // Добавляем составной уникальный индекс: один сотрудник может быть назначен только один раз на конкретную бригаду/цех с определенным типом
        Schema::table('brigade_masters', function (Blueprint $table) {
            $table->unique(['brigade_id', 'sotrudnik_id', 'type'], 'brigade_sotrudnik_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brigade_masters', function (Blueprint $table) {
            $table->dropUnique('brigade_sotrudnik_type_unique');
        });

        Schema::table('brigade_masters', function (Blueprint $table) {
            $table->unique('sotrudnik_id');
            $table->dropColumn('type');
        });
    }
};

