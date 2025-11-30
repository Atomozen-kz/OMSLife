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
        Schema::table('sotrudniki', function (Blueprint $table) {
            // Удаляем старое поле
            $table->dropColumn('position');

            // Добавляем новое поле с внешним ключом на таблицу positions
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            // Восстанавливаем старое поле
            $table->string('position')->nullable();

            // Удаляем новое поле
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
        });
    }
};
