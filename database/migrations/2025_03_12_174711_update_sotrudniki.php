<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            $table->boolean('birthday_show')->default(true)->after('organization_id');
        });
    }
    public function down(): void
    {
        Schema::table('sotrudniki', function (Blueprint $table) {
            $table->dropColumn('birthday_show');
        });
    }
};
