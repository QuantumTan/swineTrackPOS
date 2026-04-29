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
                'contact_person_first_name' => 'Ana',
                'contact_person_last_name' => 'Ramos',
                'contact_number' => '09171234567',
                'email_address' => 'ana.ramos@metrocuts.test',
                'business_address' => 'Barangay San Isidro, Tarlac City, Tarlac',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'South Valley Hog Farm',
                'contact_person_first_name' => 'Marco',
                'contact_person_last_name' => 'Dela Cruz',
                'contact_number' => '09179876543',
                'email_address' => 'purchasing@southvalley.test',
                'business_address' => 'Maharlika Highway, Cabanatuan City, Nueva Ecija',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'North Ridge Meats',
                'contact_person_first_name' => 'Liza',
                'contact_person_last_name' => 'Santos',
                'contact_number' => '09175557777',
                'email_address' => null,
                'business_address' => 'Purok 3, Urdaneta City, Pangasinan',
                'status' => 'Inactive',
            ],
            [
                'supplier_name' => 'Green Pastures Livestock',
                'contact_person_first_name' => 'Joel',
                'contact_person_last_name' => 'Navarro',
                'contact_number' => null,
                'email_address' => 'dispatch@greenpastures.test',
                'business_address' => 'San Miguel, Bulacan',
                'status' => 'Active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->create($supplier);
        }
    }
}
