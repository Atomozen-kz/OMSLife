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
        Schema::create('organization_signers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id'); // Подписант из таблицы users
            $table->string('last_name'); // ��амилия подписанта
            $table->string('first_name'); // Имя подписанта
            $table->string('father_name'); // Отчество подписанта
            $table->string('position'); // Должность подписанта
            $table->string('iin'); // ИИН подписанта
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organization_structure')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_signers');
    }
};
