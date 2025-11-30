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
        Schema::create('spravka_sotrudnikam', function (Blueprint $table) {
            $table->id();
            $table->string('iin'); // ИИН сотрудника
            $table->foreignId('organization_id')->constrained('organization_structure');
            $table->foreignId('sotrudnik_id')->constrained('sotrudniki');
            $table->integer('status')->default(1);
            $table->string('pdf_path')->nullable(); // Путь к сгенерированному PDF-файлу
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spravka_sotrudnikam');
    }
};
