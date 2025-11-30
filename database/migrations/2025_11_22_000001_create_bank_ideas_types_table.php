<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_ideas_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_kz')->nullable();
            $table->string('name_ru')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_ideas_types');
    }
};
