<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductPrice;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'images', 'prices'])->paginate(10);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = ProductCategory::all();
        $roles = Role::all();
        return view('products.create', compact('categories', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products',
            'description' => 'required|string',
            'size_chart' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'dimensions' => 'nullable|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.values' => 'required|string',
            'prices.*.role_id' => 'required|exists:roles,id',
            'prices.*.buy_price' => 'required|numeric|min:0',
            'prices.*.sell_price' => 'required|numeric|min:0',
            'prices.*.reseller_price' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sku' => $request->sku,
            'description' => $request->description,
            'size_chart' => $request->size_chart,
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
        ]);

        // Handle images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $key => $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $key === 0,
                    'sort_order' => $key,
                ]);
            }
        }

        // Handle dynamic attributes
        if ($request->has('attributes')) {
            foreach ($request->attributes as $attributeData) {
                // Create or get attribute
                $attribute = ProductAttribute::firstOrCreate(
                    ['slug' => Str::slug($attributeData['name'])],
                    ['name' => $attributeData['name']]
                );

                // Create attribute values
                $values = array_map('trim', explode(',', $attributeData['values']));
                foreach ($values as $value) {
                    $attributeValue = ProductAttributeValue::firstOrCreate(
                        [
                            'attribute_id' => $attribute->id,
                            'value' => $value
                        ]
                    );

                    // Create attribute combination
                    $product->attributeCombinations()->create([
                        'attribute_id' => $attribute->id,
                        'attribute_value_id' => $attributeValue->id,
                    ]);
                }
            }
        }

        // Handle prices
        foreach ($request->prices as $price) {
            $product->prices()->create([
                'role_id' => $price['role_id'],
                'buy_price' => $price['buy_price'],
                'sell_price' => $price['sell_price'],
                'reseller_price' => $price['reseller_price'],
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::all();
        $roles = Role::all();
        return view('products.edit', compact('product', 'categories', 'roles'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'description' => 'required|string',
            'size_chart' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'dimensions' => 'nullable|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'attributes' => 'array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.values' => 'required|string',
            'prices.*.role_id' => 'required|exists:roles,id',
            'prices.*.buy_price' => 'required|numeric|min:0',
            'prices.*.sell_price' => 'required|numeric|min:0',
            'prices.*.reseller_price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sku' => $request->sku,
            'description' => $request->description,
            'size_chart' => $request->size_chart,
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
        ]);

        // Handle images
        if ($request->hasFile('images')) {
            // Delete old images
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }

            // Upload new images
            foreach ($request->file('images') as $key => $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $key === 0,
                    'sort_order' => $key,
                ]);
            }
        }

        // Handle dynamic attributes
        $product->attributeCombinations()->delete();
        if ($request->has('attributes')) {
            foreach ($request->attributes as $attributeData) {
                // Create or get attribute
                $attribute = ProductAttribute::firstOrCreate(
                    ['slug' => Str::slug($attributeData['name'])],
                    ['name' => $attributeData['name']]
                );

                // Create attribute values
                $values = array_map('trim', explode(',', $attributeData['values']));
                foreach ($values as $value) {
                    $attributeValue = ProductAttributeValue::firstOrCreate(
                        [
                            'attribute_id' => $attribute->id,
                            'value' => $value
                        ]
                    );

                    // Create attribute combination
                    $product->attributeCombinations()->create([
                        'attribute_id' => $attribute->id,
                        'attribute_value_id' => $attributeValue->id,
                    ]);
                }
            }
        }

        // Handle prices
        $product->prices()->delete();
        foreach ($request->prices as $price) {
            $product->prices()->create([
                'role_id' => $price['role_id'],
                'buy_price' => $price['buy_price'],
                'sell_price' => $price['sell_price'],
                'reseller_price' => $price['reseller_price'],
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
