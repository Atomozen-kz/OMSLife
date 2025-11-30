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
        Schema::table('stories_category', function (Blueprint $table) {
            $table->dropColumn('name'); // Удаление столбца name
            $table->string('name_kz'); // Добавление столбца name_kz
            $table->string('name_ru'); // Добавление столбца name_ru
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories_category', function (Blueprint $table) {
            $table->dropColumn(['name_kz', 'name_ru']); // Удаление новых столбцов
            $table->string('name'); // Добавление столбца name обратно
        });
    }
};
