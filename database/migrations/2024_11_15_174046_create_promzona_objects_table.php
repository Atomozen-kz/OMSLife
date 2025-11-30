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
        Schema::create('promzona_objects', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_type')->nullable(); // Тип объекта
            $table->foreign('id_type')->references('id')->on('promzona_types')->onDelete('set null'); // Тип объекта

            $table->unsignedBigInteger('id_organization')->nullable();
            $table->foreign('id_organization')->references('id')->on('organization_structure')->onDelete('set null'); // Организация

            $table->unsignedBigInteger('id_sotrudnik')->nullable();
            $table->foreign('id_sotrudnik')->references('id')->on('sotrudniki')->onDelete('set null'); // Кто добавил

            $table->string('number')->nullable(); // Номер объекта
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('status')->default(false); // Статус проверки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promzona_objects');
    }
};
