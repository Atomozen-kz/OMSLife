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
        // Добавляем id_answer в appeal_media
        Schema::table('appeal_media', function (Blueprint $table) {
            $table->unsignedBigInteger('id_answer')->nullable()->after('id_appeal');
            $table->foreign('id_answer')->references('id')->on('appeal_answers')->onDelete('cascade');
            
            $table->index(['id_appeal', 'id_answer']);
        });

        // Убираем attachments из appeal_answers
        Schema::table('appeal_answers', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем attachments в appeal_answers
        Schema::table('appeal_answers', function (Blueprint $table) {
            $table->json('attachments')->nullable();
        });

        // Убираем id_answer из appeal_media
        Schema::table('appeal_media', function (Blueprint $table) {
            $table->dropForeign(['id_answer']);
            $table->dropIndex(['id_appeal', 'id_answer']);
            $table->dropColumn('id_answer');
        });
    }
};
