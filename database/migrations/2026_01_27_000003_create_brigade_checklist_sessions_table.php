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
        Schema::create('brigade_checklist_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_id')->comment('ID мастера бригады');
            $table->string('full_name_master')->comment('ФИО мастера');
            $table->unsignedBigInteger('brigade_id')->comment('ID бригады');
            $table->string('brigade_name')->comment('Название бригады');
            $table->string('well_number')->nullable()->comment('Номер скважины');
            $table->string('tk')->nullable()->comment('ТК');
            $table->timestamp('completed_at')->nullable()->comment('Дата и время заполнения');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('master_id')->references('id')->on('brigade_masters')->onDelete('cascade');
            $table->foreign('brigade_id')->references('id')->on('remont_brigades')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brigade_checklist_sessions');
    }
};
