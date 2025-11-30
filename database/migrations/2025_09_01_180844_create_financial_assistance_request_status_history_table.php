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
        Schema::create('financial_assistance_request_status_history', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('old_status');
            $table->tinyInteger('new_status');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_request');
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_request')->references('id')->on('financial_assistance_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_assistance_request_status_history');
    }
};
