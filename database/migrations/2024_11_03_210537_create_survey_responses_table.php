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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('cascade');
            $table->foreignId('sotrudniki_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->string('lang')->default('ru'); // Язык опроса при прохождении
            $table->timestamps();

            $table->unique(['survey_id', 'sotrudniki_id']); // Уникальность: один пользователь может пройти опрос только один раз

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
