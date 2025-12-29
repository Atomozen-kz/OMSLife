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
        Schema::create('safety_memos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pdf_file');
            $table->string('lang', 10)->default('ru');
            $table->boolean('status')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safety_memos');
    }
};
