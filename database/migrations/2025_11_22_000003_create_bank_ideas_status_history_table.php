<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_ideas_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_idea_id')->constrained('bank_ideas')->onDelete('cascade');
            $table->string('status')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('sotrudniki')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_ideas_status_history');
    }
};

