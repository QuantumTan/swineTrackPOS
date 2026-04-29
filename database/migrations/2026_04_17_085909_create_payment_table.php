<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            DB::statement('ALTER TABLE payment ADD CONSTRAINT chk_payment_amount CHECK (amount >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
