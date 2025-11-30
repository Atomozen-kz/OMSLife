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
        Schema::create('organization_structure', function (Blueprint $table) {
            $table->id();
            $table->string('name_ru'); // Название на русском
            $table->string('name_kz'); // Название на казахском
            $table->foreignId('parent_id')->nullable()->constrained('organization_structure')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_structure');
    }
};
