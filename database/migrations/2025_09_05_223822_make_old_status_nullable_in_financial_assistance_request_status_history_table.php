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
        Schema::table('financial_assistance_request_status_history', function (Blueprint $table) {
            $table->tinyInteger('old_status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_assistance_request_status_history', function (Blueprint $table) {
            $table->tinyInteger('old_status')->nullable(false)->change();
        });
    }
};
