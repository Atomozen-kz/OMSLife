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
        Schema::table('safety_memo_opened', function (Blueprint $table) {
            $table->integer('count_opened')->default(1)->after('sotrudnik_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safety_memo_opened', function (Blueprint $table) {
            $table->dropColumn('count_opened');
        });
    }
};
