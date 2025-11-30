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
        Schema::table('training_records', function (Blueprint $table) {
            $table->string('certificate_number', 255)->nullable()->after('id_sotrudnik');
            $table->string('protocol_number', 255)->nullable()->after('id_sotrudnik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->dropColumn(['certificate_number', 'protocol_number']);
        });
    }
};
