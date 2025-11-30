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
        Schema::create('sotrudniki_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sotrudnik_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->string('type')->nullable();
            $table->string('code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sotrudniki_codes');
    }
};
