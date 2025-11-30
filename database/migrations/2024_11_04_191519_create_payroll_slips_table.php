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
        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sotrudniki_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->string('full_name');
            $table->string('tabel_nomer');
            $table->string('month'); // Формат: "Октябрь 2024"
            $table->string('pdf_path'); // Путь к сохраненному PDF-файлу
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_slips');
    }
};
