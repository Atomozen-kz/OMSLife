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
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('lang')->default('ru')->after('status'); // Добавляем поле lang с дефолтным значением 'ru'
            $table->boolean('is_all')->default(true)->after('lang'); // Добавляем поле is_all с дефолтным значением true
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['lang', 'is_all']);
        });
    }
};
