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
        Schema::create('brigade_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->text('rule_text'); // Правило (ӨМҚ колонка)
            $table->string('event_name'); // Наименование мероприятия (Шара атауы)
            $table->enum('lang', ['ru', 'kz'])->default('ru'); // Язык
            $table->string('image')->nullable(); // Путь к иконке
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->boolean('status')->default(true); // Активность
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['lang', 'status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brigade_checklist_items');
    }
};
