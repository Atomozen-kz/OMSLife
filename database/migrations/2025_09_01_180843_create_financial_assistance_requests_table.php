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
        Schema::create('financial_assistance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sotrudnik');
            $table->tinyInteger('status')->default(1)->comment('1-pending, 2-approved, 3-rejected');
            $table->unsignedBigInteger('id_signer')->nullable();
            $table->unsignedBigInteger('id_type');
            $table->json('form_data')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('id_sotrudnik')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_signer')->references('id')->on('financial_assistance_signers')->onDelete('set null');
            $table->foreign('id_type')->references('id')->on('financial_assistance_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_assistance_requests');
    }
};
