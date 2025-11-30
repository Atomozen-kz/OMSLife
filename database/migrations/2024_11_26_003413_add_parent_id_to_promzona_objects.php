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
        Schema::table('promzona_objects', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->after('id')->nullable();
            $table->foreign('parent_id')->references('id')->on('promzona_objects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promzona_objects', function (Blueprint $table) {
            $table->dropForeign('promzona_objects_parent_id_foreign');
            $table->dropColumn('parent_id');
        });
    }
};
