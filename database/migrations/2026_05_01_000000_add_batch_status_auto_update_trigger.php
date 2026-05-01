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
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::unprepared('
                CREATE TRIGGER after_batch_item_update_status
                AFTER UPDATE ON batch_item
                FOR EACH ROW
                BEGIN
                    DECLARE total_qty DECIMAL(10,3);
                    
                    SELECT COALESCE(SUM(qty_in_kg), 0) INTO total_qty
                    FROM batch_item
                    WHERE batch_id = NEW.batch_id;
                    
                    IF total_qty = 0 AND (
                        SELECT batch_status FROM batch WHERE batch_id = NEW.batch_id
                    ) != "Closed" THEN
                        UPDATE batch 
                        SET batch_status = "Sold Out" 
                        WHERE batch_id = NEW.batch_id;
                    END IF;
                END
            ');
        } else if ($driver === 'sqlite') {
            DB::unprepared('
                CREATE TRIGGER after_batch_item_update_status
                AFTER UPDATE ON batch_item
                FOR EACH ROW
                WHEN (
                    SELECT COALESCE(SUM(qty_in_kg), 0) FROM batch_item 
                    WHERE batch_id = NEW.batch_id
                ) = 0
                BEGIN
                    UPDATE batch 
                    SET batch_status = "Sold Out" 
                    WHERE batch_id = NEW.batch_id 
                    AND batch_status != "Closed";
                END
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_batch_item_update_status');
    }
};
