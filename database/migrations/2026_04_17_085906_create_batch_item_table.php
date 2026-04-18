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
        Schema::create('batch_item', function (Blueprint $table) {
            $table->increments('batch_item_id');
            $table->unsignedInteger('batch_id');
            $table->unsignedInteger('product_id');
            $table->decimal('qty_in_kg', 10, 3);
            $table->decimal('cost_per_kg', 10, 2);

            $table->foreign('batch_id')
                ->references('batch_id')
                ->on('batches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });


   
            DB::statement('ALTER TABLE batch_item ADD CONSTRAINT chk_batch_qty CHECK (qty_in_kg > 0)');
            DB::statement('ALTER TABLE batch_item ADD CONSTRAINT chk_batch_cost CHECK (cost_per_kg > 0)');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_item');
    }
};
