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
        Schema::table('loyalty_cards_categories', function (Blueprint $table) {
            $table->string('image_path')->after('status')->default('/storage/loyalty_cards_category/default.png');
            $table->string('color_rgb')->after('image_path')->default("240, 240, 240");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_cards_categories', function (Blueprint $table) {
            $table->dropColumn('image_path');
            $table->dropColumn('color_rgb');
        });
    }
};
