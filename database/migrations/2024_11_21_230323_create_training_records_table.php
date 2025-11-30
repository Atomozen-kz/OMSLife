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
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_training_type')->nullable();
            $table->foreign('id_training_type')->references('id')->on('training_types')->onDelete('set null');

            $table->unsignedBigInteger('id_sotrudnik')->nullable();
            $table->foreign('id_sotrudnik')->references('id')->on('sotrudniki')->onDelete('set null');

            $table->date('completion_date'); // Дата прохождения обучения
            $table->date('validity_date');  // Дата окончания срока годности
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
