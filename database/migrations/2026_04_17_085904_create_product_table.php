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
        Schema::create('product', function (Blueprint $table) {
            $table->increments('product_id');
            $table->string('product_name', 50);
            $table->string('product_category', 30);
            $table->decimal('product_price_per_kilo', 10, 2);
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
    }
};
