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
        Schema::table('financial_assistance_requests', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('processed_at')
                ->comment('Путь к сгенерированному PDF файлу заявки');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_assistance_requests', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });
    }
};
