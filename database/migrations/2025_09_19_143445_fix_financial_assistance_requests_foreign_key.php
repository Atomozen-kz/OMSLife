<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_assistance_requests', function (Blueprint $table) {
            // Сначала удаляем внешний ключ
            $table->dropForeign(['id_sotrudnik']);

            // Делаем поле nullable
            $table->unsignedBigInteger('id_sotrudnik')->nullable()->change();

            // Создаем новый внешний ключ с set null
            $table->foreign('id_sotrudnik')
                ->references('id')
                ->on('sotrudniki')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('financial_assistance_requests', function (Blueprint $table) {
            // Откатываем изменения - удаляем новый внешний ключ
            $table->dropForeign(['id_sotrudnik']);

            // Возвращаем поле обратно в NOT NULL
            $table->unsignedBigInteger('id_sotrudnik')->nullable(false)->change();

            // Восстанавливаем старый внешний ключ на users
            $table->foreign('id_sotrudnik')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
