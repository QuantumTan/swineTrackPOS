<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->installMySqlRoutines();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropMySqlRoutines();
    }

    private function installMySqlRoutines(): void
    {
        $this->dropMySqlRoutines();

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

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_product_after_insert
AFTER INSERT ON product
FOR EACH ROW
BEGIN
    INSERT IGNORE INTO inventory (product_id, current_stock, last_updated_at)
    VALUES (NEW.product_id, 0, NOW());
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_sale_item_before_insert
BEFORE INSERT ON sale_item
FOR EACH ROW
BEGIN
    IF NEW.qty_sold_kg <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sale quantity must be greater than zero';
    END IF;

    IF NEW.price_per_kg < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sale price must not be negative';
    END IF;

    IF COALESCE((SELECT current_stock FROM inventory WHERE product_id = NEW.product_id), 0) < NEW.qty_sold_kg THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for POS sale';
    END IF;

    IF COALESCE((
        SELECT batch_item.qty_in_kg
        FROM batch_item
        INNER JOIN sale ON sale.batch_id = batch_item.batch_id
        WHERE sale.sale_id = NEW.sale_id
            AND batch_item.product_id = NEW.product_id
        LIMIT 1
    ), 0) < NEW.qty_sold_kg THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient batch stock for POS sale';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_sale_item_after_insert
AFTER INSERT ON sale_item
FOR EACH ROW
BEGIN
    UPDATE inventory
    SET current_stock = current_stock - NEW.qty_sold_kg,
        last_updated_at = NOW()
    WHERE product_id = NEW.product_id;

    UPDATE batch_item
    INNER JOIN sale ON sale.batch_id = batch_item.batch_id
    SET batch_item.qty_in_kg = batch_item.qty_in_kg - NEW.qty_sold_kg
    WHERE sale.sale_id = NEW.sale_id
        AND batch_item.product_id = NEW.product_id;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_complete_pos_sale(
    IN p_batch_id INT,
    IN p_user_id INT,
    IN p_cash_received DECIMAL(10,2),
    IN p_items JSON,
    OUT p_sale_id INT
)
BEGIN
    DECLARE v_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_short_stock_count INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        DROP TEMPORARY TABLE IF EXISTS tmp_pos_items;
        RESIGNAL;
    END;

    START TRANSACTION;

    SET v_count = JSON_LENGTH(p_items);

    CREATE TEMPORARY TABLE tmp_pos_items (
        product_id INT NOT NULL,
        qty_sold_kg DECIMAL(10,3) NOT NULL
    ) ENGINE=MEMORY;

    WHILE v_index < v_count DO
        INSERT INTO tmp_pos_items (product_id, qty_sold_kg)
        VALUES (
            CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_index, '].product_id'))) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_index, '].qty_sold_kg'))) AS DECIMAL(10,3))
        );

        SET v_index = v_index + 1;
    END WHILE;

    SELECT COALESCE(SUM(fn_pos_line_total(jt.qty_sold_kg, product.product_price_per_kilo)), 0)
    INTO v_total
    FROM tmp_pos_items AS jt
    INNER JOIN product ON product.product_id = jt.product_id;

    IF v_total <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'POS cart is empty';
    END IF;

    IF p_cash_received < v_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cash received is lower than the total amount';
    END IF;

    SELECT COUNT(*)
    INTO v_short_stock_count
    FROM tmp_pos_items AS jt
    LEFT JOIN inventory ON inventory.product_id = jt.product_id
    WHERE COALESCE(inventory.current_stock, 0) < jt.qty_sold_kg;

    IF v_short_stock_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for POS sale';
    END IF;

    INSERT INTO sale (batch_id, user_id, sale_date)
    VALUES (p_batch_id, p_user_id, NOW());

    SET p_sale_id = LAST_INSERT_ID();

    INSERT INTO sale_item (sale_id, product_id, qty_sold_kg, price_per_kg)
    SELECT p_sale_id, jt.product_id, jt.qty_sold_kg, product.product_price_per_kilo
    FROM tmp_pos_items AS jt
    INNER JOIN product ON product.product_id = jt.product_id;

    INSERT INTO payment (sale_id, amount, payment_status, payment_date)
    VALUES (p_sale_id, v_total, 'paid', NOW());

    DROP TEMPORARY TABLE IF EXISTS tmp_pos_items;

    COMMIT;
END
SQL);
    }

    private function dropMySqlRoutines(): void
    {
        foreach (
            [
                'trg_sale_item_after_insert',
                'trg_sale_item_before_insert',
                'trg_batch_item_after_insert',
                'trg_product_after_insert',
            ] as $trigger
        ) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_complete_pos_sale');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_pos_stock_status');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_pos_line_total');
    }
};
