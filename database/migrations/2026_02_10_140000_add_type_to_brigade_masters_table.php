<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Добавляем поле type если его нет
        if (!Schema::hasColumn('brigade_masters', 'type')) {
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->enum('type', ['brigade', 'workshop'])
                    ->default('brigade')
                    ->after('sotrudnik_id')
                    ->comment('Тип мастера: brigade - мастер бригады, workshop - мастер цеха');
            });
        }

        // 2. Проверяем существование старого уникального индекса
        $oldIndexExists = $this->indexExists('brigade_masters_sotrudnik_id_unique');

        if ($oldIndexExists) {
            // Сначала удаляем foreign key если есть
            $fkExists = $this->foreignKeyExists('brigade_masters_sotrudnik_id_foreign');

            if ($fkExists) {
                Schema::table('brigade_masters', function (Blueprint $table) {
                    $table->dropForeign('brigade_masters_sotrudnik_id_foreign');
                });
            }

            // Удаляем старый уникальный индекс
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->dropUnique('brigade_masters_sotrudnik_id_unique');
            });

            // Восстанавливаем foreign key без unique
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->foreign('sotrudnik_id', 'brigade_masters_sotrudnik_id_foreign')
                    ->references('id')
                    ->on('sotrudniki')
                    ->onDelete('cascade');
            });
        }

        // 3. Добавляем составной уникальный индекс если его нет
        if (!$this->indexExists('brigade_sotrudnik_type_unique')) {
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->unique(['brigade_id', 'sotrudnik_id', 'type'], 'brigade_sotrudnik_type_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем составной индекс
        if ($this->indexExists('brigade_sotrudnik_type_unique')) {
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->dropUnique('brigade_sotrudnik_type_unique');
            });
        }

        // Восстанавливаем старый уникальный индекс
        if (!$this->indexExists('brigade_masters_sotrudnik_id_unique')) {
            // Удаляем foreign key
            if ($this->foreignKeyExists('brigade_masters_sotrudnik_id_foreign')) {
                Schema::table('brigade_masters', function (Blueprint $table) {
                    $table->dropForeign('brigade_masters_sotrudnik_id_foreign');
                });
            }

            // Добавляем уникальный индекс
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->unique('sotrudnik_id', 'brigade_masters_sotrudnik_id_unique');
            });
        }

        // Удаляем поле type
        if (Schema::hasColumn('brigade_masters', 'type')) {
            Schema::table('brigade_masters', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    /**
     * Проверяет существование индекса
     */
    private function indexExists(string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM brigade_masters WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Проверяет существование foreign key
     */
    private function foreignKeyExists(string $fkName): bool
    {
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'brigade_masters'
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$fkName]);

        return !empty($fks);
    }
};

