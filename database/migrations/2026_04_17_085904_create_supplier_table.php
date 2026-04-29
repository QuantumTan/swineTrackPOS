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
            $table->string('contact_person_first_name', 50)->nullable();
            $table->string('contact_person_last_name', 50)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('status', 20)->default('Active');
            $table->string('email_address', 120)->nullable();
            $table->string('business_address', 255)->nullable();
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
