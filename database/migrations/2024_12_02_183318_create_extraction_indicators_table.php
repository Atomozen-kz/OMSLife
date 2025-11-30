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
        Schema::create('extraction_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id'); // Ссылка на компанию
            $table->foreign('company_id')->references('id')->on('extraction_companies')->onDelete('cascade');
            $table->decimal('plan', 10, 2)->nullable(); // План добычи
            $table->decimal('real', 10, 2)->nullable(); // Фактическая добыча
            $table->date('date'); // Дата показателя
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_indicators');
    }
};
