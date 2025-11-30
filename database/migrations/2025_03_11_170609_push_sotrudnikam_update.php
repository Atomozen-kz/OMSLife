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
        Schema::table('push_sotrudnikam', function (Blueprint $table) {
            $table->foreignId('recipient_id')
                ->nullable()
                ->constrained('sotrudniki')
                ->after('for_all')
                ->onDelete('set null');
            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('users')
                ->after('sotrudnik_id')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('push_sotrudnikam', function (Blueprint $table) {
            $table->dropForeign(['recipient_id']);
            $table->dropForeign(['sender_id']);
            $table->dropColumn('recipient_id');
            $table->dropColumn('sender_id');
        });
    }
};
