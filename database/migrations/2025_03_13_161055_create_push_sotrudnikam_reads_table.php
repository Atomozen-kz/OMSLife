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
        Schema::create('push_sotrudnikam_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sotrudnik_id')->nullable()->constrained('sotrudniki')->onDelete('set null');
            $table->foreignId('push_sotrudnikam_id')->nullable()->constrained('push_sotrudnikam')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_sotrudnikam_reads');
    }
};
