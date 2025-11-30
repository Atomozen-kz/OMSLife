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
        Schema::create('promzona_geo_objects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_type')->nullable(); // Связь с PromzonaType
            $table->string('name')->nullable(); // Название объекта
            $table->unsignedBigInteger('parent_id')->nullable(); // Для иерархической связи
            $table->json('geometry'); // Геометрия в формате GeoJSON
            $table->string('comment')->nullable(); // Комментарий к объекту
            $table->timestamps();

            // Внешние ключи
            $table->foreign('id_type')->references('id')->on('promzona_types')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('promzona_geo_objects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promzona_geo_objects');
    }
};
