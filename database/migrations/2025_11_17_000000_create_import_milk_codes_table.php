<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_milk_codes', function (Blueprint $table) {
            $table->id();
            $table->string('psp')->nullable();
            $table->string('tabel_number')->nullable();
            $table->string('full_name')->nullable();
            $table->string('qr')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_milk_codes');
    }
};

