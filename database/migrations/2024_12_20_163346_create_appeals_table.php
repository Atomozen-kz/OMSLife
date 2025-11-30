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
        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('id_topic');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_org');
            $table->timestamps();

            $table->foreign('id_topic')->references('id')->on('appeal_topics');
            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_org')->references('id')->on('organization_structure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appeals');
    }
};
