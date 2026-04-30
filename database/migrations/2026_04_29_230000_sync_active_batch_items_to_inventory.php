<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('batch') || ! Schema::hasTable('batch_item') || ! Schema::hasTable('inventory')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
UPDATE batch_item
INNER JOIN batch ON batch.batch_id = batch_item.batch_id
INNER JOIN inventory ON inventory.product_id = batch_item.product_id
SET batch_item.qty_in_kg = inventory.current_stock
WHERE batch.batch_status != 'Closed'
SQL);

            return;
        }

        DB::statement(<<<'SQL'
UPDATE batch_item
SET qty_in_kg = COALESCE((
    SELECT inventory.current_stock
    FROM inventory
    WHERE inventory.product_id = batch_item.product_id
), 0)
WHERE batch_id IN (
    SELECT batch.batch_id
    FROM batch
    WHERE batch.batch_status != 'Closed'
)
SQL);
    }

    public function down(): void
    {
        //
    }
};
