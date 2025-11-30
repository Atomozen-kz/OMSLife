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
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('photo'); // Удаление столбца photo
            $table->string('media'); // Добавление столбца media для хранения ссылки на медиафайл
            $table->enum('type', ['image', 'video'])->default('image'); // Добавление столбца type для указания типа медиафайла
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('photo'); // Восстановление столбца photo
            $table->dropColumn(['media', 'type']); // Удаление столбцов media и type
        });
    }
};
