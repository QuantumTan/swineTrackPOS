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
                'email_address' => 'contact@northridge.test',
                'business_address' => 'Purok 3, Urdaneta City, Pangasinan',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'Green Pastures Livestock',
                'contact_person_first_name' => 'Joel',
                'contact_person_last_name' => 'Navarro',
                'contact_number' => '09165551234',
                'email_address' => 'dispatch@greenpastures.test',
                'business_address' => 'San Miguel, Bulacan',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'Prime Pork Processing',
                'contact_person_first_name' => 'Carlos',
                'contact_person_last_name' => 'Gonzales',
                'contact_number' => '09163334444',
                'email_address' => 'sales@primeporkcorp.test',
                'business_address' => 'Nueva Ecija Industrial Zone',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'Sunrise Farms Cooperative',
                'contact_person_first_name' => 'Rosa',
                'contact_person_last_name' => 'Reyes',
                'contact_number' => '09155556666',
                'email_address' => 'orders@sunrisefarms.test',
                'business_address' => 'Pangasinan',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'Valley Fresh Meats',
                'contact_person_first_name' => 'Pedro',
                'contact_person_last_name' => 'Morales',
                'contact_number' => '09177778888',
                'email_address' => 'info@valleyfresh.test',
                'business_address' => 'Cabanatuan City, Nueva Ecija',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'Heritage Pork House',
                'contact_person_first_name' => 'Monica',
                'contact_person_last_name' => 'Cruz',
                'contact_number' => '09169999999',
                'email_address' => 'heritage@porkhouse.test',
                'business_address' => 'Tarlac City, Tarlac',
                'status' => 'Active',
            ],
            [
                'supplier_name' => 'Quality Livestock Imports',
                'contact_person_first_name' => 'Vincent',
                'contact_person_last_name' => 'Santos',
                'contact_number' => '09161111111',
                'email_address' => 'import@qualityls.test',
                'business_address' => 'NAIA Cargo, Pasay City',
                'status' => 'Inactive',
            ],
            [
                'supplier_name' => 'Direct Farm Supply',
                'contact_person_first_name' => 'Elena',
                'contact_person_last_name' => 'Torres',
                'contact_number' => '09162222222',
                'email_address' => 'direct@farmsupply.test',
                'business_address' => 'Laguna',
                'status' => 'Active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->firstOrCreate(
                ['supplier_name' => $supplier['supplier_name']],
                $supplier
            );
        }
    }
}
