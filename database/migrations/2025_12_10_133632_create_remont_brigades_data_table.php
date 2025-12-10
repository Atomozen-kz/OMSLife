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
        Schema::create('remont_brigades_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brigade_id');
            $table->string('month_year', 7); // Формат: "2025-01"
            $table->integer('plan')->default(0);
            $table->integer('fact')->default(0);
            $table->foreign('brigade_id')->references('id')->on('remont_brigades')->onDelete('cascade');
            $table->unique(['brigade_id', 'month_year']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remont_brigades_data');
    }
};
