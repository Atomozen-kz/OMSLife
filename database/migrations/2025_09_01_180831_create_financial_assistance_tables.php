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
        Schema::create('financial_assistance_types_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'textarea', 'date', 'file'])->default('text');
            $table->boolean('required')->default(false);
            $table->integer('sort')->default(0);
            $table->timestamps();
            
            $table->foreign('id_type')->references('id')->on('financial_assistance_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_assistance_types_rows');
    }
};
