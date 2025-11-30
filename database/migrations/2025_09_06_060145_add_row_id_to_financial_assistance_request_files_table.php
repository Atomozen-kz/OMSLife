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
        Schema::table('financial_assistance_request_files', function (Blueprint $table) {
            $table->unsignedBigInteger('row_id')->nullable()->after('field_name');
            $table->foreign('row_id')->references('id')->on('financial_assistance_types_rows')->onDelete('cascade');
            $table->index('row_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_assistance_request_files', function (Blueprint $table) {
            $table->dropForeign(['row_id']);
            $table->dropIndex(['row_id']);
            $table->dropColumn('row_id');
        });
    }
};
