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
        Schema::create('push_read_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_id')->constrained('push_sotrudnikam')->onDelete('cascade');
            $table->foreignId('sotrudnik_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_read_status');
    }
};
