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
        Schema::create('brigade_checklist_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('brigade_checklist_sessions')
                ->onDelete('cascade')
                ->comment('ID сессии заполнения чек-листа');
            $table->foreignId('checklist_item_id')
                ->constrained('brigade_checklist_items')
                ->onDelete('cascade')
                ->comment('ID пункта чек-листа');
            $table->enum('response_type', ['dangerous', 'safe', 'other'])
                ->comment('Тип ответа: опасно, безопасно, другое');
            $table->text('response_text')->nullable()
                ->comment('Текст ответа для типа "другое"');
            $table->timestamps();

            // Индексы для фильтрации и отчетов
            $table->index(['session_id', 'response_type']);
            $table->index('checklist_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brigade_checklist_responses');
    }
};
