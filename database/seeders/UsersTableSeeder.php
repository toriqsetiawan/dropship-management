<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
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
                'role_id'    => 1,
                'name'    => 'Administrator',
                'email'    => 'toriqbagus@gmail.com',
                'password'    => bcrypt('Administrator.123')
            ],
            [
                'role_id'    => 2,
                'name'    => 'Admin',
                'email'    => 'sales.artfootwear@gmail.com',
                'password'    => bcrypt('Admin.123')
            ],
            [
                'role_id'    => 3,
                'name'    => 'Distributor Internal',
                'email'    => 'distributor.internal@gmail.com',
                'password'    => bcrypt('Distributor.123')
            ],
            [
                'role_id'    => 3,
                'name'    => 'Distributor Menside',
                'email'    => 'distributor.menside@gmail.com',
                'password'    => bcrypt('Distributor.123')
            ],
            [
                'role_id'    => 3,
                'name'    => 'Distributor Bristle',
                'email'    => 'distributor.bristle@gmail.com',
                'password'    => bcrypt('Distributor.123')
            ],
            [
                'role_id'    => 3,
                'name'    => 'Reseller DR.FOE',
                'email'    => 'reseller.drfoe@gmail.com',
                'password'    => bcrypt('Reseller.123')
            ],
            [
                'role_id'    => 3,
                'name'    => 'Reseller Mala',
                'email'    => 'reseller.mala@gmail.com',
                'password'    => bcrypt('Reseller.123')
            ],
            [
                'role_id'    => 3,
                'name'    => 'Reseller Qomar',
                'email'    => 'reseller.qomar@gmail.com',
                'password'    => bcrypt('Reseller.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Shopee Mall Tosant',
                'email'    => 'tosantmall.shopee@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Shopee Excel',
                'email'    => 'excel.shopee@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Shopee Black Edition',
                'email'    => 'blackedition.shopee@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Shopee Hafiz Sport',
                'email'    => 'hafizsport.shopee@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Shopee Tosant',
                'email'    => 'tosant.shopee@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Tokopedia Art Footwear',
                'email'    => 'artfootwear.tokopedia@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Lazada Art Footwear',
                'email'    => 'artfootwear.lazada@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Lazada Hafiz Sport',
                'email'    => 'hafizsport.lazada@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Tiktok Art Footwear',
                'email'    => 'artfootwear.tiktok@gmail.com',
                'password'    => bcrypt('Retail.123')
            ],
            [
                'role_id'    => 4,
                'name'    => 'Offline',
                'email'    => 'offline@gmail.com',
                'password'    => bcrypt('Retail.123')
            ]
        ];

        foreach ($data as $user) {
            User::create($user);
        }
    }
}
