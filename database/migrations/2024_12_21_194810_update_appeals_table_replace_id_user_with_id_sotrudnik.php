<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('appeals', function (Blueprint $table) {
            // Удаляем колонку id_user
            $table->dropForeign(['id_user']); // Если есть внешний ключ
            $table->dropColumn('id_user');

            $table->tinyInteger('status')->default(1); // Добавляем новую колонку status
            // Добавляем колонку id_sotrudnik
            $table->unsignedBigInteger('id_sotrudnik')->nullable();

            // Если нужно добавить внешний ключ
            $table->foreign('id_sotrudnik')->references('id')->on('sotrudniki')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('appeals', function (Blueprint $table) {
            // Удаляем колонку id_sotrudnik
            $table->dropForeign(['id_sotrudnik']);
            $table->dropColumn('id_sotrudnik');
            $table->dropColumn('status');

            // Восстанавливаем колонку id_user
            $table->unsignedBigInteger('id_user')->nullable();
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }
};
