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
            $table->unsignedBigInteger('id_signer')->nullable()->after('sotrudnik_id');
            $table->foreign('id_signer')->references('id')->on('organization_signers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spravka_sotrudnikam', function (Blueprint $table) {
            $table->dropForeign('spravka_sotrudnikam_id_signer_foreign');
            $table->dropColumn('id_signer');
        });
    }
};
