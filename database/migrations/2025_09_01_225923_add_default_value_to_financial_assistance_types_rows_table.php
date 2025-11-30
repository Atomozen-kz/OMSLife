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
        Schema::table('financial_assistance_types_rows', function (Blueprint $table) {
            $table->text('default_value')->nullable()->after('type')
                ->comment('Значение по умолчанию (может содержать плейсхолдеры типа {{user.full_name}})');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_assistance_types_rows', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });
    }
};
