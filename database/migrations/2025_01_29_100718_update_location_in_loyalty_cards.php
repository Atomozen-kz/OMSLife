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
        Schema::table('loyalty_cards', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->string('lat')->nullable()->after('description');
            $table->string('lng')->nullable()->after('lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_cards', function (Blueprint $table) {
            $table->string('location')->nullable()->after('description');
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
