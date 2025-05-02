<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Supplier 1',
                'email' => 'supplier1@example.com',
                'phone' => '08123456789',
                'status' => 'active',
            ],
            [
                'name' => 'Supplier 2',
                'email' => 'supplier2@example.com',
                'phone' => '08987654321',
                'status' => 'active',
            ]
        ];

        foreach ($data as $supplier) {
            Supplier::create($supplier);
        }
    }
}
