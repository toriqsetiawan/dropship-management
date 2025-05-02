<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'administrator', 'description' => 'System administrator'],
            ['name' => 'admin', 'description' => 'Admins linked to stores'],
            ['name' => 'distributor', 'description' => 'Distributor linked to stores'],
            ['name' => 'reseller', 'description' => 'Resellers linked to stores'],
            ['name' => 'retail', 'description' => 'Retail linked to stores'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
