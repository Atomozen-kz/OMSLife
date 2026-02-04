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
        Schema::create('safety_memo_opened', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('safety_memo_id');
            $table->unsignedBigInteger('sotrudnik_id');
            $table->timestamps();

            $table->foreign('safety_memo_id')->references('id')->on('safety_memos')->onDelete('cascade');
            $table->foreign('sotrudnik_id')->references('id')->on('sotrudniki')->onDelete('cascade');

            // Уникальный индекс для предотвращения дублирования записей
            $table->unique(['safety_memo_id', 'sotrudnik_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safety_memo_opened');
    }
};
