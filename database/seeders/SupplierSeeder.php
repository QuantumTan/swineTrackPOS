<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_name' => 'Metro Cuts Trading',
                'supplier_contact_first_name' => 'Ana',
                'supplier_contact_last_name' => 'Ramos',
                'supplier_phone_number' => '09171234567',
                'supplier_email' => 'ana.ramos@metrocuts.test',
                'supplier_address' => 'Barangay San Isidro, Tarlac City, Tarlac',
                'supplier_status' => 'Active',
            ],
            [
                'supplier_name' => 'South Valley Hog Farm',
                'supplier_contact_first_name' => 'Marco',
                'supplier_contact_last_name' => 'Dela Cruz',
                'supplier_phone_number' => '09179876543',
                'supplier_email' => 'purchasing@southvalley.test',
                'supplier_address' => 'Maharlika Highway, Cabanatuan City, Nueva Ecija',
                'supplier_status' => 'Active',
            ],
            [
                'supplier_name' => 'North Ridge Meats',
                'supplier_contact_first_name' => 'Liza',
                'supplier_contact_last_name' => 'Santos',
                'supplier_phone_number' => '09175557777',
                'supplier_email' => null,
                'supplier_address' => 'Purok 3, Urdaneta City, Pangasinan',
                'supplier_status' => 'Inactive',
            ],
            [
                'supplier_name' => 'Green Pastures Livestock',
                'supplier_contact_first_name' => 'Joel',
                'supplier_contact_last_name' => 'Navarro',
                'supplier_phone_number' => null,
                'supplier_email' => 'dispatch@greenpastures.test',
                'supplier_address' => 'San Miguel, Bulacan',
                'supplier_status' => 'Active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->create($supplier);
        }
    }
}
