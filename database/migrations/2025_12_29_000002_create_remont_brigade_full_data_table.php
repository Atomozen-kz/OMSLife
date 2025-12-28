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
        Schema::create('remont_brigade_full_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->integer('well_number'); // № скважины
            $table->string('tk')->nullable(); // ТҚ
            $table->string('mk_kkss')->nullable(); // МК/ ҚҚСС
            $table->decimal('unv_hours', 8, 1)->default(0); // ІУН сағ (укрупненная норма времени)
            $table->decimal('actual_hours', 8, 1)->default(0); // Нақты сағ (фактические часы)
            $table->date('start_date')->nullable(); // Күрделі жөндеу: Басы
            $table->date('end_date')->nullable(); // Күрделі жөндеу: Соңы
            $table->text('description')->nullable(); // Түрі (описание работ)
            $table->foreign('plan_id')->references('id')->on('remont_brigades_plan')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remont_brigade_full_data');
    }
};

