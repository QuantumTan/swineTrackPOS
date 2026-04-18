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

      
            DB::statement('ALTER TABLE inventory ADD CONSTRAINT chk_inventory_stock CHECK (current_stock_kg >= 0)');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
