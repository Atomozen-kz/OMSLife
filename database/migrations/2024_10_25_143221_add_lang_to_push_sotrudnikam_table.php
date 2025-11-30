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
        Schema::table('push_sotrudnikam', function (Blueprint $table) {
            $table->string('lang', 2)->default('ru')->after('body'); // Добавляет столбец lang с коротким кодом языка
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('push_sotrudnikam', function (Blueprint $table) {
            $table->dropColumn('lang');
        });
    }
};
