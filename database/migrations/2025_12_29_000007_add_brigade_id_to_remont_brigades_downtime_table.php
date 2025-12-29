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
        Schema::table('remont_brigades_downtime', function (Blueprint $table) {
            $table->foreignId('brigade_id')->nullable()->after('plan_id')->constrained('remont_brigades')->onDelete('cascade');
            $table->index('brigade_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remont_brigades_downtime', function (Blueprint $table) {
            $table->dropForeign(['brigade_id']);
            $table->dropIndex(['brigade_id']);
            $table->dropColumn('brigade_id');
        });
    }
};

