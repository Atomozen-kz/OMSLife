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
        Schema::create('promzona_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_kz')->nullable(); // Название KZ
            $table->string('name_ru')->nullable(); // Название RU
            $table->boolean('status')->default(true); // Название RU
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promzona_types');
    }
};
