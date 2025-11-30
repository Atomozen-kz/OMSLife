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
        Schema::table('services_vars', function (Blueprint $table) {
            $table->string('description_kz')->nullable()->after('name_kz');
            $table->string('description_ru')->nullable()->after("name_ru");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services_vars', function (Blueprint $table) {
            $table->dropColumn('description_kz');
            $table->dropColumn('description_ru');
        });
    }
};
