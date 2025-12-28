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
        Schema::create('remont_brigades_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brigade_id');
            $table->string('month', 7); // Формат: "2025-12"
            $table->integer('plan')->default(0); // Количество ремонтируемых скважин
            $table->foreign('brigade_id')->references('id')->on('remont_brigades')->onDelete('cascade');
            $table->unique(['brigade_id', 'month']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remont_brigades_plan');
    }
};
