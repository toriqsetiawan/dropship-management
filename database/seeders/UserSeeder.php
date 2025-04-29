<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get role IDs
        $productOwnerRole = Role::where('slug', 'product_owner')->first();
        $distributorRole = Role::where('slug', 'distributor')->first();
        $resellerRole = Role::where('slug', 'reseller')->first();

        // Create Administrator
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $productOwnerRole->id,
        ]);

        // Create Accounting Admin
        $accounting = User::create([
            'name' => 'Accounting Admin',
            'email' => 'accounting@example.com',
            'password' => Hash::make('password'),
            'role_id' => $productOwnerRole->id,
        ]);

        // Create Distributors
        $distributor1 = User::create([
            'name' => 'Distributor 1',
            'email' => 'distributor1@example.com',
            'password' => Hash::make('password'),
            'role_id' => $distributorRole->id,
            'parent_id' => $admin->id,
        ]);

        $distributor2 = User::create([
            'name' => 'Distributor 2',
            'email' => 'distributor2@example.com',
            'password' => Hash::make('password'),
            'role_id' => $distributorRole->id,
            'parent_id' => $admin->id,
        ]);

        // Create Resellers for Distributor 1
        User::create([
            'name' => 'Reseller 1-1',
            'email' => 'reseller11@example.com',
            'password' => Hash::make('password'),
            'role_id' => $resellerRole->id,
            'parent_id' => $distributor1->id,
        ]);

        User::create([
            'name' => 'Reseller 1-2',
            'email' => 'reseller12@example.com',
            'password' => Hash::make('password'),
            'role_id' => $resellerRole->id,
            'parent_id' => $distributor1->id,
        ]);

        // Create Resellers for Distributor 2
        User::create([
            'name' => 'Reseller 2-1',
            'email' => 'reseller21@example.com',
            'password' => Hash::make('password'),
            'role_id' => $resellerRole->id,
            'parent_id' => $distributor2->id,
        ]);

        User::create([
            'name' => 'Reseller 2-2',
            'email' => 'reseller22@example.com',
            'password' => Hash::make('password'),
            'role_id' => $resellerRole->id,
            'parent_id' => $distributor2->id,
        ]);
    }
}
