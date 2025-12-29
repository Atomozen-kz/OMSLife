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
        Schema::create('remont_brigades_downtime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('remont_brigades_plan')->onDelete('cascade');
            $table->string('reason');
            $table->integer('hours')->default(0);
            $table->timestamps();

            $table->index(['plan_id', 'reason']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remont_brigades_downtime');
    }
};
