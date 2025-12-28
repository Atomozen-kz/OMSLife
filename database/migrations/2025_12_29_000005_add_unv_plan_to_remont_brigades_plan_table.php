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
        Schema::table('remont_brigades_plan', function (Blueprint $table) {
            $table->integer('unv_plan')->default(0)->after('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remont_brigades_plan', function (Blueprint $table) {
            $table->dropColumn('unv_plan');
        });
    }
};

