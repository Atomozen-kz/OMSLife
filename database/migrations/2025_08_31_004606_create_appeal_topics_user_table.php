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
        Schema::create('appeal_topics_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_topic');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();
            
            // Добавляем внешние ключи
            $table->foreign('id_topic')->references('id')->on('appeal_topics')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            
            // Уникальный индекс для предотвращения дублирования
            $table->unique(['id_topic', 'id_user']);
            
            // Индексы для быстрого поиска
            $table->index('id_topic');
            $table->index('id_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appeal_topics_user');
    }
};
