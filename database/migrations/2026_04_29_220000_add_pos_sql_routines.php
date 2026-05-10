<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropViews();

        if (DB::getDriverName() === 'mysql') {
            $this->dropMySqlObjects();
            $this->installFunctions();
            $this->installTriggers();
        }

        $this->installViews();
    }

    public function down(): void
    {
        $this->dropViews();

        if (DB::getDriverName() === 'mysql') {
            $this->dropMySqlObjects();
        }
    }

    private function installFunctions(): void
    {
        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_pos_line_total(p_qty DECIMAL(10,3), p_price DECIMAL(10,2))
RETURNS DECIMAL(10,2)
DETERMINISTIC
RETURN ROUND(p_qty * p_price, 2)
SQL);

        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_pos_stock_status(p_stock DECIMAL(10,3))
RETURNS VARCHAR(20)
DETERMINISTIC
RETURN CASE
    WHEN p_stock <= 0 THEN 'Out of Stock'
    WHEN p_stock < 20 THEN 'Low Stock'
    ELSE 'In Stock'
END
SQL);
    }

    private function installTriggers(): void
    {
        // Auto-initialize inventory record when a new product is created - Lourde
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_product_after_insert
AFTER INSERT ON product
FOR EACH ROW
BEGIN
    INSERT IGNORE INTO inventory (product_id, current_stock_kg, last_updated_at)
    VALUES (NEW.product_id, 0, NOW());
END
SQL);

        // Sync inventory stock total and update batch status when batch items are added - Lourde
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_batch_item_after_insert 
AFTER INSERT ON batch_item
FOR EACH ROW
BEGIN
    INSERT INTO inventory (product_id, current_stock_kg, last_updated_at)
    VALUES (NEW.product_id, (SELECT COALESCE(SUM(qty_in_kg), 0) FROM batch_item WHERE product_id = NEW.product_id), NOW())
    ON DUPLICATE KEY UPDATE
        current_stock_kg = (SELECT COALESCE(SUM(qty_in_kg), 0) FROM batch_item WHERE product_id = NEW.product_id),
        last_updated_at = NOW();

    UPDATE batch
    SET batch_status = CASE WHEN EXISTS (SELECT 1 FROM batch_item WHERE batch_id = NEW.batch_id AND qty_in_kg > 0) THEN 'Open' ELSE 'Sold Out' END
    WHERE batch_id = NEW.batch_id AND batch_status != 'Closed';
END
SQL);

        // Recalculate inventory stock when batch item quantities are updated - Jonathan
        DB::unprepared(<<<'SQL'
CREATE TRIGGER after_batch_item_update_sync_inventory
AFTER UPDATE ON batch_item
FOR EACH ROW
BEGIN
    UPDATE inventory
    SET current_stock_kg = (SELECT COALESCE(SUM(qty_in_kg), 0) FROM batch_item WHERE product_id = NEW.product_id),
        last_updated_at = NOW()
    WHERE product_id = NEW.product_id;
END
SQL);

        // Update batch status (Open/Sold Out) when items in batch are updated - Lourde
        DB::unprepared(<<<'SQL'
CREATE TRIGGER after_batch_item_update_status
AFTER UPDATE ON batch_item
FOR EACH ROW
BEGIN
    UPDATE batch
    SET batch_status = CASE WHEN EXISTS (SELECT 1 FROM batch_item WHERE batch_id = NEW.batch_id AND qty_in_kg > 0) THEN 'Open' ELSE 'Sold Out' END
    WHERE batch_id = NEW.batch_id AND batch_status != 'Closed';

    IF OLD.batch_id <> NEW.batch_id THEN
        UPDATE batch
        SET batch_status = CASE WHEN EXISTS (SELECT 1 FROM batch_item WHERE batch_id = OLD.batch_id AND qty_in_kg > 0) THEN 'Open' ELSE 'Sold Out' END
        WHERE batch_id = OLD.batch_id AND batch_status != 'Closed';
    END IF;
END
SQL);

        // Recalculate inventory and update batch status when batch items are deleted - Lourde
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_batch_item_after_delete
AFTER DELETE ON batch_item
FOR EACH ROW
BEGIN
    UPDATE inventory
    SET current_stock_kg = (SELECT COALESCE(SUM(qty_in_kg), 0) FROM batch_item WHERE product_id = OLD.product_id),
        last_updated_at = NOW()
    WHERE product_id = OLD.product_id;

    UPDATE batch
    SET batch_status = CASE WHEN EXISTS (SELECT 1 FROM batch_item WHERE batch_id = OLD.batch_id AND qty_in_kg > 0) THEN 'Open' ELSE 'Sold Out' END
    WHERE batch_id = OLD.batch_id AND batch_status != 'Closed';
END
SQL);

        // Validate sale quantities and available stock before allowing sale item insertion - Jonathan
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_sale_item_before_insert
BEFORE INSERT ON sale_item
FOR EACH ROW
BEGIN
    IF NEW.qty_sold_kg <= 0 OR NEW.price_per_kg < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sale quantity must be positive and price non-negative';
    END IF;

    IF COALESCE((SELECT qty_in_kg FROM batch_item INNER JOIN sale ON sale.batch_id = batch_item.batch_id WHERE sale.sale_id = NEW.sale_id AND batch_item.product_id = NEW.product_id LIMIT 1), 0) < NEW.qty_sold_kg THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient batch stock for POS sale';
    END IF;
END
SQL);

        // Deduct sold quantity from batch inventory after sale is confirmed -  Jonathan
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_sale_item_after_insert
AFTER INSERT ON sale_item
FOR EACH ROW
BEGIN
    UPDATE batch_item
    INNER JOIN sale ON sale.batch_id = batch_item.batch_id
    SET batch_item.qty_in_kg = batch_item.qty_in_kg - NEW.qty_sold_kg
    WHERE sale.sale_id = NEW.sale_id
        AND batch_item.product_id = NEW.product_id;
END
SQL);

        // Prevent supplier deletion if they have active (Open/Pending) batches - Jonathan
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_supplier_before_delete
BEFORE DELETE ON supplier
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM batch
        WHERE supplier_id = OLD.supplier_id
        AND batch_status IN ('Open', 'Pending')
    ) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Cannot delete supplier with active batches';
    END IF;
END
SQL);
    }



    private function installViews(): void
    {
        // Product inventory overview with current stock levels and status (Out of Stock, Low Stock, In Stock) - Lourde
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_product_inventory AS
SELECT
    product.product_id,
    product.product_name,
    category.category_name,
    product.product_price_per_kilo,
    COALESCE(inventory.current_stock_kg, 0) AS current_stock,
    inventory.last_updated_at,
    CASE
        WHEN COALESCE(inventory.current_stock_kg, 0) <= 0 THEN 'Out of Stock'
        WHEN COALESCE(inventory.current_stock_kg, 0) < 20 THEN 'Low Stock'
        ELSE 'In Stock'
    END AS stock_status
FROM product
LEFT JOIN category ON category.category_id = product.category_id
LEFT JOIN inventory ON inventory.product_id = product.product_id
SQL);

        // Products with current stock below 20kg threshold (reorder alert) - Jonathan
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_low_stock_products AS
SELECT
    product_id,
    product_name,
    current_stock,
    stock_status
FROM vw_product_inventory
WHERE current_stock < 20
SQL);

        // Batch details with product breakdown, quantities, and cost calculations - Lourde
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_batch_details AS
SELECT
    batch.batch_id,
    batch.batch_date,
    batch.source_type,
    batch.batch_status,
    CASE
        WHEN batch.source_type = 'Own Livestock' THEN 'Own Livestock'
        ELSE COALESCE(supplier.supplier_name, 'N/A')
    END AS supplier_name,
    user.user_email,
    batch_item.batch_item_id,
    batch_item.product_id,
    product.product_name,
    batch_item.qty_in_kg,
    batch_item.cost_per_kg,
    ROUND(batch_item.qty_in_kg * batch_item.cost_per_kg, 2) AS line_total_cost
FROM batch_item
INNER JOIN batch ON batch.batch_id = batch_item.batch_id
INNER JOIN product ON product.product_id = batch_item.product_id
INNER JOIN user ON user.user_id = batch.user_id
LEFT JOIN supplier ON supplier.supplier_id = batch.supplier_id
SQL);

        // Individual sale items with product, user, and line total information - Jonathan
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_sales_details AS
SELECT
    sale.sale_id,
    sale.sale_date,
    sale.batch_id,
    user.user_email,
    sale_item.sale_item_id,
    sale_item.product_id,
    product.product_name,
    category.category_name,
    sale_item.qty_sold_kg,
    sale_item.price_per_kg,
    ROUND(sale_item.qty_sold_kg * sale_item.price_per_kg, 2) AS line_total
FROM sale_item
INNER JOIN sale ON sale.sale_id = sale_item.sale_id
INNER JOIN product ON product.product_id = sale_item.product_id
LEFT JOIN category ON category.category_id = product.category_id
INNER JOIN user ON user.user_id = sale.user_id
SQL);

        // Daily sales summary showing transaction count and total sales per day - Lourde
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_daily_sales_summary AS
SELECT
    DATE(sale_date) AS sale_day,
    COUNT(DISTINCT sale_id) AS total_transactions,
    ROUND(SUM(line_total), 2) AS total_sales
FROM vw_sales_details
GROUP BY DATE(sale_date)
SQL);

        // Payment and sale summary with item count, quantities sold, and totals -  Jonathan
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_payment_summary AS
SELECT
    sale.sale_id,
    sale.sale_date,
    sale.batch_id,
    user.user_email,
    payment.payment_status,
    payment.payment_date,
    payment.amount,
    COUNT(sale_item.sale_item_id) AS item_count,
    COALESCE(SUM(sale_item.qty_sold_kg), 0) AS total_qty_sold_kg,
    ROUND(COALESCE(SUM(sale_item.qty_sold_kg * sale_item.price_per_kg), 0), 2) AS total_line_sales
FROM sale
INNER JOIN user ON user.user_id = sale.user_id
LEFT JOIN payment ON payment.sale_id = sale.sale_id
LEFT JOIN sale_item ON sale_item.sale_id = sale.sale_id
GROUP BY
    sale.sale_id,
    sale.sale_date,
    sale.batch_id,
    user.user_email,
    payment.payment_status,
    payment.payment_date,
    payment.amount
SQL);
    }

    private function dropViews(): void
    {
        foreach (
            [
                'vw_payment_summary',
                'vw_daily_sales_summary',
                'vw_sales_details',
                'vw_batch_details',
                'vw_low_stock_products',
                'vw_product_inventory',
            ] as $view
        ) {
            DB::unprepared("DROP VIEW IF EXISTS {$view}");
        }
    }

    private function dropMySqlObjects(): void
    {
        foreach (
            [
                'trg_sale_item_after_insert',
                'trg_sale_item_before_insert',
                'after_batch_item_update_status',
                'after_batch_item_update_sync_inventory',
                'trg_batch_item_after_delete',
                'trg_batch_item_after_update',
                'trg_batch_item_after_insert',
                'trg_product_after_insert',
            ] as $trigger
        ) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }

        DB::unprepared('DROP FUNCTION IF EXISTS fn_pos_stock_status');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_pos_line_total');
    }
};
