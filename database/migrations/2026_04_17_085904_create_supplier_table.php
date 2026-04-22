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
        Schema::create('supplier', function (Blueprint $table) {
            $table->increments('supplier_id');
            $table->string('supplier_name', 100);
            $table->string('supplier_contact_first_name', 50)->nullable();
            $table->string('supplier_contact_last_name', 50)->nullable();
            $table->string('supplier_phone_number', 20)->nullable();
            $table->string('supplier_email', 120)->nullable();
            $table->string('supplier_address', 255)->nullable();
            $table->string('supplier_payment_terms', 80)->nullable();
            $table->enum('supplier_status', ['Active', 'Inactive'])->default('Active');
            $table->text('supplier_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier');
    }
};
