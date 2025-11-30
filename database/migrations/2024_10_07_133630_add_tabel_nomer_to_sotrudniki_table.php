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
            $table->integer('tabel_nomer')->after('iin')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            $table->dropColumn('tabel_nomer');
        });
    }
};
