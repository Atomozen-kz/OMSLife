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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question')->nullable(false);
            $table->text('answer')->nullable(false);
            $table->boolean('status')->default(true);
            $table->tinyInteger('sort')->default(111);
            $table->string('lang')->default('kz');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('id_user')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
