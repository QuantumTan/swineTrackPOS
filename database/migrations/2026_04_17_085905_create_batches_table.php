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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch');
    }
};
