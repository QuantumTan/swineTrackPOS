<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vw_daily_sales_summary');
        DB::unprepared('DROP VIEW IF EXISTS vw_sales_details');

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

        DB::unprepared(<<<'SQL'
CREATE VIEW vw_daily_sales_summary AS
SELECT
    DATE(sale_date) AS sale_day,
    COUNT(DISTINCT sale_id) AS total_transactions,
    ROUND(SUM(line_total), 2) AS total_sales
FROM vw_sales_details
GROUP BY DATE(sale_date)
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vw_daily_sales_summary');
        DB::unprepared('DROP VIEW IF EXISTS vw_sales_details');

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
    }
};
