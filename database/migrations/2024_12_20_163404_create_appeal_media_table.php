<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('appeal_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_appeal');
            $table->string('file_path');
            $table->string('file_type');
            $table->timestamps();

            $table->foreign('id_appeal')->references('id')->on('appeals');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appeal_media');
    }
};
