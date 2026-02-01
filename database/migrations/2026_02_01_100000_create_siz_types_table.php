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
        Schema::create('siz_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ru');
            $table->string('name_kz');
            $table->string('unit_ru');
            $table->string('unit_kz');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siz_types');
    }
};
