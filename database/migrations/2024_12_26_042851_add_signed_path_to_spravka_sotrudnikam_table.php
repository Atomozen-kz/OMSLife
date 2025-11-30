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
        Schema::table('spravka_sotrudnikam', function (Blueprint $table) {
            $table->string('signed_path')->nullable()->after('pdf_path');
            $table->dateTime('signed_at')->nullable()->after('signed_path');
            $table->string('signed_iin')->nullable()->after('signed_at');
            $table->string('certificate_serial')->nullable()->after('signed_iin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spravka_sotrudnikam', function (Blueprint $table) {
            $table->dropColumn('signed_path');
            $table->dropColumn('signed_at');
            $table->dropColumn('signed_iin');
            $table->dropColumn('certificate_serial');
        });
    }
};
