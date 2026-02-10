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
        Schema::table('gps_devices', function (Blueprint $table) {
            $table->foreignId('brigade_id')
                ->nullable()
                ->after('protocol')
                ->constrained('remont_brigades')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_devices', function (Blueprint $table) {
            $table->dropForeign(['brigade_id']);
            $table->dropColumn('brigade_id');
        });
    }
};

