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
            // Удаляем старые поля name_ru и name_kz
            $table->dropColumn(['name_ru', 'name_kz']);

            // Добавляем новое поле name и lang
            $table->string('name')->after('id');
            $table->string('lang', 2)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories_category', function (Blueprint $table) {
            // Восстанавливаем удаленные поля name_ru и name_kz
            $table->string('name_ru')->nullable();
            $table->string('name_kz')->nullable();

            // Удаляем добавленные поля name и lang
            $table->dropColumn(['name', 'lang']);
        });
    }
};
