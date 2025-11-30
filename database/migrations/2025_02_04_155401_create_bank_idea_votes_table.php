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
        Schema::create('bank_ideas_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_idea')->constrained('bank_ideas')->cascadeOnDelete();
            $table->foreignId('id_sotrudnik')->constrained('sotrudniki')->cascadeOnDelete();
            $table->enum('vote', ['up', 'down']); // Голос (+1 или -1)
            $table->timestamps();

            $table->unique(['id_idea', 'id_sotrudnik']); // Уникальный голос от сотрудника
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_ideas_votes');
    }
};
