<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            // Сначала создаем новое поле full_name и заполняем его данными
            $table->string('full_name')->nullable()->after('id');
        });

        // Заполняем full_name из существующих данных
        DB::statement("UPDATE sotrudniki SET full_name = TRIM(CONCAT(COALESCE(last_name, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(father_name, '')))");

        Schema::table('sotrudniki', function (Blueprint $table) {
            // Удаляем старые поля
            $table->dropColumn(['first_name', 'last_name', 'father_name', 'is_payroll_slip_func']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            // Восстанавливаем старые поля
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('father_name')->nullable()->after('last_name');
            $table->boolean('is_payroll_slip_func')->default(false)->after('photo_profile');

            // Удаляем full_name
            $table->dropColumn('full_name');
        });
    }
};

