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
        Schema::table('remont_brigade_full_data', function (Blueprint $table) {
            $table->string('ngdu')->nullable()->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remont_brigade_full_data', function (Blueprint $table) {
            $table->dropColumn('ngdu');
        });
    }
};

