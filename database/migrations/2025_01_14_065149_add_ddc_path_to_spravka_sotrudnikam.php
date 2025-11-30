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
        Schema::table('spravka_sotrudnikam', function (Blueprint $table) {
            $table->string('ddc_path')->nullable()->after('signed_path');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spravka_sotrudnikam', function (Blueprint $table) {
            $table->dropColumn('ddc_path');
        });
    }
};
