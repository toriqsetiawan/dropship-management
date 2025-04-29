<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Product Owner',
                'slug' => 'product_owner',
                'description' => 'Product owner with full access to manage products and pricing'
            ],
            [
                'name' => 'Distributor',
                'slug' => 'distributor',
                'description' => 'Distributor with access to manage resellers and view products'
            ],
            [
                'name' => 'Reseller',
                'slug' => 'reseller',
                'description' => 'Reseller with access to view and sell products'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']], // Check if exists by slug
                $role // Data to create if it doesn't exist
            );
        }
    }
}