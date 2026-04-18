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
        Schema::create('sale_item', function (Blueprint $table) {
            $table->increments('sale_item_id');
            $table->unsignedInteger('sale_id');
            $table->unsignedInteger('product_id');
            $table->decimal('qty_sold_kg', 10, 3);
            $table->decimal('price_per_kg', 10, 2);

            $table->foreign('sale_id')
                ->references('sale_id')
                ->on('sale')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

     
            DB::statement('ALTER TABLE sale_item ADD CONSTRAINT chk_sale_qty CHECK (qty_sold_kg > 0)');
            DB::statement('ALTER TABLE sale_item ADD CONSTRAINT chk_sale_price CHECK (price_per_kg > 0)');

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_item');
    }
};
