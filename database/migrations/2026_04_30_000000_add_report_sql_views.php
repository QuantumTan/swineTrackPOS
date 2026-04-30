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
        $this->dropViews();

        DB::unprepared(<<<'SQL'
CREATE VIEW vw_product_inventory AS
SELECT
    product.product_id,
    product.product_name,
    category.category_name,
    product.product_price_per_kilo,
    COALESCE(inventory.current_stock, 0) AS current_stock,
    inventory.last_updated_at,
    CASE
        WHEN COALESCE(inventory.current_stock, 0) <= 0 THEN 'Out of Stock'
        WHEN COALESCE(inventory.current_stock, 0) < 20 THEN 'Low Stock'
        ELSE 'In Stock'
    END AS stock_status
FROM product
LEFT JOIN category ON category.category_id = product.category_id
LEFT JOIN inventory ON inventory.product_id = product.product_id
SQL);

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
    sale_item.qty_sold_kg,
    sale_item.price_per_kg,
    ROUND(sale_item.qty_sold_kg * sale_item.price_per_kg, 2) AS line_total
FROM sale_item
INNER JOIN sale ON sale.sale_id = sale_item.sale_id
INNER JOIN product ON product.product_id = sale_item.product_id
INNER JOIN user ON user.user_id = sale.user_id
SQL);

        DB::unprepared(<<<'SQL'
CREATE VIEW vw_daily_sales_summary AS
SELECT
    DATE(sale_date) AS sale_day,
    COUNT(DISTINCT sale_id) AS total_transactions,
    ROUND(SUM(line_total), 2) AS total_sales
FROM vw_sales_details
GROUP BY DATE(sale_date)
SQL);

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropViews();
    }

    private function dropViews(): void
    {
        foreach ([
            'vw_payment_summary',
            'vw_daily_sales_summary',
            'vw_sales_details',
            'vw_batch_details',
            'vw_low_stock_products',
            'vw_product_inventory',
        ] as $view) {
            DB::unprepared("DROP VIEW IF EXISTS {$view}");
        }
    }
};
