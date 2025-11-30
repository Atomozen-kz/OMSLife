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
        Schema::create('push_sotrudnikam', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('mini_description');
            $table->text('body');
            $table->string('photo')->nullable();
            $table->boolean('sended')->default(false);
            $table->boolean('for_all')->default(false);
            $table->timestamps();
        });

        Schema::create('organization_push', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_id')->constrained('push_sotrudnikam')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organization_structure')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('push_sotrudnikam', function (Blueprint $table) {
            $table->dropForeign('organization_push_push_id_foreign');
            $table->dropForeign('organization_push_organization_id_foreign');
        });


        Schema::dropIfExists('organization_push');
        Schema::dropIfExists('push_sotrudnikam');
    }
};
