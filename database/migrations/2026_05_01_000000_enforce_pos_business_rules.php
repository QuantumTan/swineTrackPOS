<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $fallbackCategoryId = DB::table('category')
            ->where('category_name', 'Uncategorized')
            ->value('category_id');

        if (! $fallbackCategoryId && DB::table('product')->whereNull('category_id')->exists()) {
            $fallbackCategoryId = DB::table('category')->insertGetId([
                'category_name' => 'Uncategorized',
                'category_description' => 'Fallback category assigned during business-rule enforcement.',
            ]);
        }

        if ($fallbackCategoryId) {
            DB::table('product')
                ->whereNull('category_id')
                ->update(['category_id' => $fallbackCategoryId]);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product MODIFY category_id INT UNSIGNED NOT NULL');

            if (! $this->mysqlCheckConstraintExists('batch', 'chk_batch_source_supplier')) {
                DB::statement("ALTER TABLE batch ADD CONSTRAINT chk_batch_source_supplier CHECK ((source_type = 'Supplier' AND supplier_id IS NOT NULL) OR (source_type = 'Own Livestock' AND supplier_id IS NULL))");
            }
        } elseif (DB::getDriverName() !== 'sqlite') {
            Schema::table('product', function (Blueprint $table) {
                $table->unsignedInteger('category_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if ($this->mysqlCheckConstraintExists('batch', 'chk_batch_source_supplier')) {
                DB::statement('ALTER TABLE batch DROP CHECK chk_batch_source_supplier');
            }

            DB::statement('ALTER TABLE product MODIFY category_id INT UNSIGNED NULL');
        } elseif (DB::getDriverName() !== 'sqlite') {
            Schema::table('product', function (Blueprint $table) {
                $table->unsignedInteger('category_id')->nullable()->change();
            });
        }
    }

    private function mysqlCheckConstraintExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.CHECK_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', $constraint)
            ->whereRaw('CONSTRAINT_NAME IN (
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND CONSTRAINT_TYPE = "CHECK"
            )', [DB::getDatabaseName(), $table])
            ->exists();
    }
};
