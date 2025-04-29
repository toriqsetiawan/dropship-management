<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Shoes', 'slug' => 'shoes', 'description' => 'All types of shoes'],
            ['name' => 'Sandals', 'slug' => 'sandals', 'description' => 'All types of sandals'],
            ['name' => 'Packs', 'slug' => 'packs', 'description' => 'Shoe packs and bundles'],
            ['name' => 'Insoles', 'slug' => 'insoles', 'description' => 'Shoe insoles and accessories'],
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }
    }
}
