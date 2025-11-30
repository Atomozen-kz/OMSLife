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
        Schema::create('sotrudniki', function (Blueprint $table) {
            $table->id();
            $table->string('fio');
            $table->date('birthdate');
            $table->string('iin')->unique();
            $table->string('phone_number')->unique();
            $table->string('position');
            $table->boolean('is_registered')->default(false);
            $table->foreignId('organization_id')->nullable()->constrained('organization_structure')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sotrudniki');
    }
};
