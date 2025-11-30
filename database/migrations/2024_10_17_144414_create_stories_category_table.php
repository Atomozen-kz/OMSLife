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
        Schema::create('stories_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar');  // Поле для хранения ссылки на изображение
            $table->boolean('status')->default(true);  // Статус категории (активна или нет)
            $table->integer('sort')->default(0);  // Поле для сортировки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories_category');
    }
};
