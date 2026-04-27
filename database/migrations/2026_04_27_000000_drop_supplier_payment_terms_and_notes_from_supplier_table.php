<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('supplier', 'supplier_payment_terms')) {
            Schema::table('supplier', function (Blueprint $table) {
                $table->dropColumn('supplier_payment_terms');
            });
        }

        if (Schema::hasColumn('supplier', 'supplier_notes')) {
            Schema::table('supplier', function (Blueprint $table) {
                $table->dropColumn('supplier_notes');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('supplier', 'supplier_payment_terms')) {
            Schema::table('supplier', function (Blueprint $table) {
                $table->string('supplier_payment_terms', 80)->nullable();
            });
        }

        if (! Schema::hasColumn('supplier', 'supplier_notes')) {
            Schema::table('supplier', function (Blueprint $table) {
                $table->text('supplier_notes')->nullable();
            });
        }
    }
};
