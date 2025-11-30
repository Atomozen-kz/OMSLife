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
        Schema::create('bank_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Название идеи
            $table->text('description'); // Описание
            $table->boolean('status')->default(false); // Статус идеи
            $table->foreignId('id_sotrudnik')->constrained('sotrudniki')->cascadeOnDelete(); // Автор
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_ideas');
    }
};
