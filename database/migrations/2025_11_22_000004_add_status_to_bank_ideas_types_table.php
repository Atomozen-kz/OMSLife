<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bank_ideas_types', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_ideas_types', 'status')) {
                $table->boolean('status')->default(true)->after('name_ru');
            }
        });
    }

    public function down()
    {
        Schema::table('bank_ideas_types', function (Blueprint $table) {
            if (Schema::hasColumn('bank_ideas_types', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

