<?php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'Size' => ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'],
            'Color' => ['Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Brown', 'Gray'],
            'Material' => ['Leather', 'Synthetic', 'Canvas', 'Mesh', 'Rubber'],
            'Gender' => ['Men', 'Women', 'Unisex'],
        ];

        foreach ($attributes as $name => $values) {
            $attribute = ProductAttribute::create([
                'name' => $name,
                'slug' => strtolower($name),
            ]);

            foreach ($values as $value) {
                ProductAttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }
        }
    }
}
