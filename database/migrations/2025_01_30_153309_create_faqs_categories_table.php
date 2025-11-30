<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('faqs_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_kz')->nullable();
            $table->string('name_ru')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->foreignId('id_category')->nullable()->constrained('faqs_categories')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['id_category']);
            $table->dropColumn('id_category');
        });

        Schema::dropIfExists('faqs_categories');
    }
};
