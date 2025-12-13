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
        Schema::create('partner_place_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_place_id')->constrained('partner_places')->onDelete('cascade');
            $table->foreignId('sotrudnik_id')->constrained('sotrudniki')->onDelete('cascade');
            $table->datetime('visited_at');
            $table->timestamps();

            // Индекс для быстрого поиска по сотруднику и месту
            $table->index(['partner_place_id', 'sotrudnik_id', 'visited_at'], 'pp_visits_place_sotrudnik_visited_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_place_visits');
    }
};

