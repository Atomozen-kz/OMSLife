<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bank_ideas', function (Blueprint $table) {
            // type_id: связь с bank_ideas_types
            if (!Schema::hasColumn('bank_ideas', 'type_id')) {
                $table->foreignId('type_id')->nullable()->constrained('bank_ideas_types')->nullOnDelete();
            }

            if (!Schema::hasColumn('bank_ideas', 'problem')) {
                $table->text('problem')->nullable();
            }

            if (!Schema::hasColumn('bank_ideas', 'solution')) {
                $table->text('solution')->nullable();
            }

            if (!Schema::hasColumn('bank_ideas', 'expected_effect')) {
                $table->text('expected_effect')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('bank_ideas', function (Blueprint $table) {
            if (Schema::hasColumn('bank_ideas', 'expected_effect')) {
                $table->dropColumn('expected_effect');
            }

            if (Schema::hasColumn('bank_ideas', 'solution')) {
                $table->dropColumn('solution');
            }

            if (Schema::hasColumn('bank_ideas', 'problem')) {
                $table->dropColumn('problem');
            }

            if (Schema::hasColumn('bank_ideas', 'type_id')) {
                // попытка удалить внешний ключ, затем столбец
                try {
                    $table->dropForeign(['type_id']);
                } catch (\Throwable $e) {
                    // ignore if foreign key doesn't exist
                }

                try {
                    $table->dropColumn('type_id');
                } catch (\Throwable $e) {
                    // ignore if column already removed
                }
            }
        });
    }
};

