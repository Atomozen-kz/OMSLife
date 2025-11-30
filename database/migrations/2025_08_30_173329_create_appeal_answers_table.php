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
        Schema::create('appeal_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_appeal');
            $table->unsignedBigInteger('answered_by')->nullable(); // ID пользователя, который ответил
            $table->text('answer'); // Текст ответа
            $table->json('attachments')->nullable(); // Прикрепленные файлы (JSON array)
            $table->boolean('is_public')->default(true); // Виден ли ответ публично
            $table->timestamps();

            $table->foreign('id_appeal')->references('id')->on('appeals')->onDelete('cascade');
            $table->foreign('answered_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['id_appeal', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appeal_answers');
    }
};
