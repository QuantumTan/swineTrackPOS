<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale', function (Blueprint $table) {
            $table->increments('sale_id');
            $table->unsignedInteger('batch_id');
            $table->unsignedInteger('user_id');
            $table->dateTime('sale_date');

            $table->foreign('batch_id')
                ->references('batch_id')
                ->on('batches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale');
    }
};
