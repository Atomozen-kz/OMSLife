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
        Schema::create('appeal_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_appeal');
            $table->tinyInteger('old_status')->nullable(); // Предыдущий статус
            $table->tinyInteger('new_status'); // Новый статус
            $table->unsignedBigInteger('changed_by')->nullable(); // ID пользователя, который изменил статус
            $table->text('comment')->nullable(); // Комментарий к изменению статуса
            $table->timestamps();

            $table->foreign('id_appeal')->references('id')->on('appeals')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['id_appeal', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appeal_status_histories');
    }
};
