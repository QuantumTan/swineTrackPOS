<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name', 50);
            $table->string('category_description', 255)->nullable();
        });

        Schema::create('supplier', function (Blueprint $table) {
            $table->increments('supplier_id');
            $table->string('supplier_name', 100);
            $table->string('contact_person_first_name', 50)->nullable();
            $table->string('contact_person_last_name', 50)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('status', 20)->default('Active');
            $table->string('email_address', 120)->nullable();
            $table->string('business_address', 255)->nullable();
        });

        Schema::create('product', function (Blueprint $table) {
            $table->increments('product_id');
            $table->unsignedInteger('category_id');
            $table->string('product_name', 50);
            $table->decimal('product_price_per_kilo', 10, 2);

            $table->foreign('category_id')
                ->references('category_id')
                ->on('category')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('batch', function (Blueprint $table) {
            $table->increments('batch_id');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->dateTime('batch_date');
            $table->enum('source_type', ['Supplier', 'Own Livestock']);
            $table->enum('batch_status', ['Open', 'Sold Out', 'Closed']);

            $table->foreign('supplier_id')
                ->references('supplier_id')
                ->on('supplier')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('batch_item', function (Blueprint $table) {
            $table->increments('batch_item_id');
            $table->unsignedInteger('batch_id');
            $table->unsignedInteger('product_id');
            $table->decimal('qty_in_kg', 10, 3);
            $table->decimal('cost_per_kg', 10, 2);

            $table->foreign('batch_id')
                ->references('batch_id')
                ->on('batch')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('inventory', function (Blueprint $table) {
            $table->increments('inventory_id');
            $table->unsignedInteger('product_id')->unique();
            $table->decimal('current_stock_kg', 10, 3)->default(0);
            $table->dateTime('last_updated_at');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('sale', function (Blueprint $table) {
            $table->increments('sale_id');
            $table->unsignedInteger('batch_id');
            $table->unsignedInteger('user_id');
            $table->dateTime('sale_date');

            $table->foreign('batch_id')
                ->references('batch_id')
                ->on('batch')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

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

        Schema::create('payment', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->unsignedInteger('sale_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->dateTime('payment_date');

            $table->foreign('sale_id')
                ->references('sale_id')
                ->on('sale')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE product ADD CONSTRAINT chk_product_price CHECK (product_price_per_kilo >= 0)');
            DB::statement("ALTER TABLE batch ADD CONSTRAINT chk_batch_source_supplier CHECK ((source_type = 'Supplier' AND supplier_id IS NOT NULL) OR (source_type = 'Own Livestock' AND supplier_id IS NULL))");
            DB::statement('ALTER TABLE batch_item ADD CONSTRAINT chk_batch_qty CHECK (qty_in_kg >= 0)');
            DB::statement('ALTER TABLE batch_item ADD CONSTRAINT chk_batch_cost CHECK (cost_per_kg >= 0)');
            DB::statement('ALTER TABLE inventory ADD CONSTRAINT chk_inventory_stock CHECK (current_stock_kg >= 0)');
            DB::statement('ALTER TABLE sale_item ADD CONSTRAINT chk_sale_qty CHECK (qty_sold_kg >= 0)');
            DB::statement('ALTER TABLE sale_item ADD CONSTRAINT chk_sale_price CHECK (price_per_kg >= 0)');
            DB::statement('ALTER TABLE payment ADD CONSTRAINT chk_payment_amount CHECK (amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
        Schema::dropIfExists('sale_item');
        Schema::dropIfExists('sale');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('batch_item');
        Schema::dropIfExists('batch');
        Schema::dropIfExists('product');
        Schema::dropIfExists('supplier');
        Schema::dropIfExists('category');
    }
};
