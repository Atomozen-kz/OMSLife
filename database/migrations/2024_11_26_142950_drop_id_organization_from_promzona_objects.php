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
            $table->dropForeign('promzona_objects_id_organization_foreign');
            $table->dropColumn('id_organization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promzona_objects', function (Blueprint $table) {
            $table->unsignedBigInteger('id_organization')->nullable();
            $table->foreign('id_organization')->references('id')->on('organization_structure')->onDelete('set null');
        });
    }
};
