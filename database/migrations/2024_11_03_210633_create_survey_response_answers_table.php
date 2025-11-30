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
        Schema::create('survey_response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained('survey_responses')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('surveys_questions')->onDelete('cascade');
            $table->foreignId('answer_id')->nullable()->constrained('surveys_answers')->onDelete('cascade'); // Для выбранных вариантов ответов
            $table->text('user_text_response')->nullable(); // Для текстовых ответов
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_response_answers');
    }
};
