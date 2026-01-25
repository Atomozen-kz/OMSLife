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
        Schema::create('logistics_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('lang', ['ru', 'kz']);
            $table->enum('type', ['excel', 'word', 'pdf']);
            $table->string('file')->nullable();
            $table->timestamps();

            $table->index('lang');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_documents');
    }
};
