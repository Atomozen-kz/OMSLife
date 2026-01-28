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
        Schema::create('brigade_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigade_id')
                ->constrained('remont_brigades')
                ->onDelete('cascade')
                ->comment('ID бригады');
            $table->foreignId('sotrudnik_id')
                ->unique()
                ->constrained('sotrudniki')
                ->onDelete('cascade')
                ->comment('ID сотрудника (мастера) - один мастер на одну бригаду');
            $table->timestamp('assigned_at')->nullable()->comment('Дата назначения мастера');
            $table->timestamps();
            $table->softDeletes(); // Для soft delete

            // Индекс для быстрого поиска по бригаде
            $table->index('brigade_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brigade_masters');
    }
};
