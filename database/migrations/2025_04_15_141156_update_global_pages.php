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
        Schema::table('global_pages', function (Blueprint $table) {
            $table->renameColumn('body','body_kz');
            $table->longText('body_ru');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_pages', function (Blueprint $table) {
            $table->dropColumn('body_kz');
            $table->dropColumn('body_ru');
        });
    }
};
