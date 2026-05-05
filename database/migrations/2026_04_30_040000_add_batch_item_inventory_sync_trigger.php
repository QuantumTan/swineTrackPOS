<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER after_batch_item_update_sync_inventory
            AFTER UPDATE ON batch_item
            FOR EACH ROW
            BEGIN
                DECLARE qty_change DECIMAL(10,3);
                SET qty_change = NEW.qty_in_kg - OLD.qty_in_kg;
                
                UPDATE inventory 
                SET current_stock_kg = current_stock_kg + qty_change,
                    last_updated_at = NOW()
                WHERE product_id = NEW.product_id;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_batch_item_update_sync_inventory');
    }
};
