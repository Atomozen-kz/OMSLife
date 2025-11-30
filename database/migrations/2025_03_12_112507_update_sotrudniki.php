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
        Schema::table('sotrudniki', function (Blueprint $table) {
            $table->string('lang')->default('kz')->after('organization_id');
            $table->string('gender')->nullable()->after('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            $table->dropColumn('lang');
            $table->dropColumn('gender');
        });
    }
};
