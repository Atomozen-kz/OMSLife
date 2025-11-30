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
        // Удаляем текущий внешний ключ
        Schema::table('news_media', function (Blueprint $table) {
            $table->dropForeign(['news_id']);
        });

        // Создаем новый внешний ключ с каскадным удалением
        Schema::table('news_media', function (Blueprint $table) {
            $table->foreign('news_id')
                ->references('id')
                ->on('news')
                ->onDelete('cascade');  // При удалении новости удаляются медиа
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстанавливаем предыдущий внешний ключ, если нужно
        Schema::table('news_media', function (Blueprint $table) {
            $table->dropForeign(['news_id']);

            $table->foreign('news_id')
                ->references('id')
                ->on('news')
                ->onDelete('restrict'); // Или старое поведение
        });
    }
};
