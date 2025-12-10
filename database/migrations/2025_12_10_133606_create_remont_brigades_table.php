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
        Schema::create('remont_brigades', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название цеха или бригады (ОМС-1, Цех № 1 и т.д.)
            $table->unsignedBigInteger('parent_id')->nullable(); // NULL = цех, иначе = бригада
            $table->foreign('parent_id')->references('id')->on('remont_brigades')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remont_brigades');
    }
};
