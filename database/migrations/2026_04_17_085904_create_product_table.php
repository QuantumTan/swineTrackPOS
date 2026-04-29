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
        Schema::create('category', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name', 50);
            $table->string('category_description', 255)->nullable();
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

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE product ADD CONSTRAINT chk_product_price CHECK (product_price_per_kilo >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
        Schema::dropIfExists('category');
    }
};
