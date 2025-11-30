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
        Schema::create('news_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sotrudnik_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['sotrudnik_id', 'news_id']); // Уникальность лайка для пары сотрудник-новость

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_likes');
    }
};
