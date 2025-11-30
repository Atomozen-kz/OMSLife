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
        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->dropColumn(['last_name', 'first_name', 'father_name']);
        });
    }
};
