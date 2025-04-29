<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Administrator
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'product_owner',
        ]);

        // Create Accounting Admin
        $accounting = User::create([
            'name' => 'Accounting Admin',
            'email' => 'accounting@example.com',
            'password' => Hash::make('password'),
            'role' => 'product_owner',
        ]);

        // Create Distributors
        $distributor1 = User::create([
            'name' => 'Distributor 1',
            'email' => 'distributor1@example.com',
            'password' => Hash::make('password'),
            'role' => 'distributor',
            'parent_id' => $admin->id,
        ]);

        $distributor2 = User::create([
            'name' => 'Distributor 2',
            'email' => 'distributor2@example.com',
            'password' => Hash::make('password'),
            'role' => 'distributor',
            'parent_id' => $admin->id,
        ]);

        // Create Resellers for Distributor 1
        User::create([
            'name' => 'Reseller 1-1',
            'email' => 'reseller11@example.com',
            'password' => Hash::make('password'),
            'role' => 'reseller',
            'parent_id' => $distributor1->id,
        ]);

        User::create([
            'name' => 'Reseller 1-2',
            'email' => 'reseller12@example.com',
            'password' => Hash::make('password'),
            'role' => 'reseller',
            'parent_id' => $distributor1->id,
        ]);

        // Create Resellers for Distributor 2
        User::create([
            'name' => 'Reseller 2-1',
            'email' => 'reseller21@example.com',
            'password' => Hash::make('password'),
            'role' => 'reseller',
            'parent_id' => $distributor2->id,
        ]);

        User::create([
            'name' => 'Reseller 2-2',
            'email' => 'reseller22@example.com',
            'password' => Hash::make('password'),
            'role' => 'reseller',
            'parent_id' => $distributor2->id,
        ]);
    }
}
