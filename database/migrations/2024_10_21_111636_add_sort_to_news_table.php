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
        Schema::table('news', function (Blueprint $table) {
            // Проверяем, существует ли колонка photo_id перед удалением
            if (Schema::hasColumn('news', 'photo_id')) {
                $table->dropColumn('photo_id');
            }

            // Добавляем новую колонку sort, если её ещё нет
            if (!Schema::hasColumn('news', 'sort')) {
                $table->integer('sort')->default(0)->after('on_main');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // Добавляем обратно колонку photo_id
            $table->unsignedBigInteger('photo_id')->nullable()->after('on_main');

            // Удаляем колонку sort
            $table->dropColumn('sort');
        });
    }
};
