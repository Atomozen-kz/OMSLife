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
        Schema::create('financial_assistance_request_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_request');
            $table->string('field_name'); // Имя поля из form_data
            $table->string('file_path'); // Путь к файлу
            $table->string('original_name'); // Оригинальное имя файла
            $table->string('file_type'); // MIME тип файла
            $table->bigInteger('file_size'); // Размер файла в байтах
            $table->timestamps();

            $table->foreign('id_request')->references('id')->on('financial_assistance_requests')->onDelete('cascade');
            $table->index(['id_request', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_assistance_request_files');
    }
};
