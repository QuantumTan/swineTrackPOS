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
        if (Schema::hasTable('users') && ! Schema::hasTable('user')) {
            Schema::rename('users', 'user');
        }

        if (Schema::hasTable('batches') && ! Schema::hasTable('batch')) {
            Schema::rename('batches', 'batch');
        }

        $this->renameColumnIfPresent('user', 'user_password', 'user_password_hash');

        $this->renameColumnIfPresent('supplier', 'supplier_contact_first_name', 'contact_person_first_name');
        $this->renameColumnIfPresent('supplier', 'supplier_contact_last_name', 'contact_person_last_name');
        $this->renameColumnIfPresent('supplier', 'supplier_phone_number', 'contact_number');
        $this->renameSupplierStatusColumn();
        $this->renameColumnIfPresent('supplier', 'supplier_email', 'email_address');
        $this->renameColumnIfPresent('supplier', 'supplier_address', 'business_address');

        $this->normalizeProductCategory();
        $this->renameColumnIfPresent('inventory', 'current_stock_kg', 'current_stock');

        if (! Schema::hasTable('payment')) {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function renameColumnIfPresent(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($from, $to) {
            $table->renameColumn($from, $to);
        });
    }

    private function renameSupplierStatusColumn(): void
    {
        if (! Schema::hasTable('supplier') || ! Schema::hasColumn('supplier', 'supplier_status') || Schema::hasColumn('supplier', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE supplier CHANGE supplier_status status VARCHAR(20) NOT NULL DEFAULT 'Active'");

            return;
        }

        Schema::table('supplier', function (Blueprint $table) {
            $table->renameColumn('supplier_status', 'status');
        });
    }

    private function normalizeProductCategory(): void
    {
        if (! Schema::hasTable('product')) {
            return;
        }

        if (! Schema::hasTable('category')) {
            Schema::create('category', function (Blueprint $table) {
                $table->increments('category_id');
                $table->string('category_name', 50);
                $table->string('category_description', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('product', 'category_id')) {
            Schema::table('product', function (Blueprint $table) {
                $table->unsignedInteger('category_id')->nullable()->after('product_id');
            });
        }

        if (Schema::hasColumn('product', 'product_category')) {
            DB::table('product')
                ->select('product_category')
                ->whereNotNull('product_category')
                ->distinct()
                ->orderBy('product_category')
                ->pluck('product_category')
                ->each(function (string $categoryName): void {
                    DB::table('category')->updateOrInsert(
                        ['category_name' => $categoryName],
                        ['category_description' => null]
                    );
                });

            DB::table('product')
                ->whereNotNull('product_category')
                ->orderBy('product_id')
                ->get(['product_id', 'product_category'])
                ->each(function (object $product): void {
                    $categoryId = DB::table('category')
                        ->where('category_name', $product->product_category)
                        ->value('category_id');

                    DB::table('product')
                        ->where('product_id', $product->product_id)
                        ->update(['category_id' => $categoryId]);
                });

            Schema::table('product', function (Blueprint $table) {
                $table->dropColumn('product_category');
            });
        }
    }
};
