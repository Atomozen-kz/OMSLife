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
        Schema::table('safety_memos', function (Blueprint $table) {
            $table->renameColumn('pdf_file', 'url');
        });

        Schema::table('safety_memos', function (Blueprint $table) {
            $table->string('type', 20)->default('pdf')->after('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safety_memos', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('safety_memos', function (Blueprint $table) {
            $table->renameColumn('url', 'pdf_file');
        });
    }
};
