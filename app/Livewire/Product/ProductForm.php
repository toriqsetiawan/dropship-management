<?php

namespace App\Livewire\Product;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Livewire\Component;
use Illuminate\Support\Str;

class ProductForm extends Component
{
    public $product;
    public $productId;
    public $name;
    public $supplier_id;
    public $factory_price;
    public $distributor_price;
    public $reseller_price;
    public $retail_price;

    public $productAttributes = [];
    public $selectedAttributes = [];
    public $attributeValues = [];
    public $variants = [];
    public $newAttributeKey = '';
    public $newAttributeValues = '';
    public $editingAttributeId = null;
    public $bulkPrice = '';
    public $bulkStock = '';
    public $bulkSkuPrefix = '';
    public $hasUnsavedChanges = false;
    protected $listeners = ['setProductAttributes'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'supplier_id' => 'required|exists:suppliers,id',
        'factory_price' => 'required|numeric|min:0',
        'distributor_price' => 'required|numeric|min:0',
        'reseller_price' => 'required|numeric|min:0',
        'variants' => 'required|array|min:1',
        'variants.*.sku' => 'required|string|distinct',
        'variants.*.stock' => 'required|integer|min:0',
        'variants.*.retail_price' => 'required|numeric|min:0',
        'newAttributeKey' => 'nullable|string|max:255',
        'newAttributeValues' => 'nullable|string',
    ];

    public function mount($productId = null)
    {
        // Try to load from localStorage (via browser event)
        $this->dispatch('load-attributes-from-storage');

        if ($productId) {
            $this->productId = $productId;
            $this->product = Product::with(['variants.attributeValues.attribute'])->findOrFail($productId);
            $this->name = $this->product->name;
            $this->supplier_id = $this->product->supplier_id;
            $this->factory_price = $this->product->factory_price;
            $this->distributor_price = $this->product->distributor_price;
            $this->reseller_price = $this->product->reseller_price;

            // Extract unique attributes and their values from variants (rollback: only name and values as strings)
            $attributeMap = [];
            foreach ($this->product->variants as $variant) {
                foreach ($variant->attributeValues as $value) {
                    $attrId = $value->attribute->id;
                    $attrName = $value->attribute->name;
                    if (!isset($attributeMap[$attrId])) {
                        $attributeMap[$attrId] = [
                            'name' => $attrName,
                            'values' => [],
                        ];
                    }
                    if (!in_array($value->value, $attributeMap[$attrId]['values'])) {
                        $attributeMap[$attrId]['values'][] = $value->value;
                    }
                }
            }
            $this->productAttributes = array_values($attributeMap);
        }
    }

    public function setProductAttributes($attributes)
    {
        $this->productAttributes = $attributes;
        $this->hasUnsavedChanges = true;
    }

    public function addAttribute($name, $values)
    {
        $this->productAttributes[] = [
            'name' => $name,
            'values' => $values, // array of strings
        ];
        $this->hasUnsavedChanges = true;
        $this->dispatch('attribute-changed', ['attributes' => $this->productAttributes]);
    }

    public function editAttribute($index, $name, $values)
    {
        $this->productAttributes[$index] = [
            'name' => $name,
            'values' => $values,
        ];
        $this->hasUnsavedChanges = true;
        $this->dispatch('attribute-changed', ['attributes' => $this->productAttributes]);
    }

    public function deleteAttribute($index)
    {
        array_splice($this->productAttributes, $index, 1);
        $this->hasUnsavedChanges = true;
        $this->dispatch('attribute-changed', ['attributes' => $this->productAttributes]);
    }

    public function updatedFactoryPrice()
    {
        // Calculate distributor price (25% markup)
        $this->distributor_price = $this->factory_price * 1.25;

        // Calculate reseller price (25% markup from distributor)
        $this->reseller_price = $this->distributor_price * 1.25;

        // Calculate retail price (25% markup from reseller)
        $this->retail_price = $this->reseller_price * 1.25;
    }

    public function addNewAttribute()
    {
        if (count($this->selectedAttributes) >= 2) {
            session()->flash('error', __('product.messages.max_attributes'));
            return;
        }

        $this->validate([
            'newAttributeKey' => 'required|string|max:255',
            'newAttributeValues' => 'required|string',
        ]);

        // Create new attribute with title case
        $attribute = Attribute::create([
            'name' => Str::title($this->newAttributeKey),
        ]);

        // Create attribute values in capital case
        $values = collect(explode(',', $this->newAttributeValues))
            ->map(fn($value) => trim($value))
            ->map(fn($value) => Str::upper($value))
            ->filter()
            ->map(fn($value) => [
                'value' => $value,
                'attribute_id' => $attribute->id,
            ]);

        AttributeValue::insert($values->toArray());

        // Reset form
        $this->newAttributeKey = '';
        $this->newAttributeValues = '';

        // Reload attributes
        $this->loadAttributes();
    }

    public function loadAttributes()
    {
        $this->productAttributes = Attribute::with('values')->get();
    }

    public function loadExistingVariants()
    {
        if (!$this->product) return;

        // Clear selectedAttributes before loading
        $this->selectedAttributes = [];

        foreach ($this->product->variants as $variant) {
            $attributeSet = [];
            foreach ($variant->attributeValues as $value) {
                $attributeSet[$value->attribute_id] = $value->id;

                // Add to selected attributes if not already selected
                if (!in_array($value->attribute_id, $this->selectedAttributes)) {
                    $this->selectedAttributes[] = $value->attribute_id;
                }
            }

            $this->variants[] = [
                'id' => $variant->id,
                'attributes' => $attributeSet,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'retail_price' => $variant->retail_price,
            ];
        }
        // Ensure only unique attribute IDs
        $this->selectedAttributes = array_values(array_unique($this->selectedAttributes));
    }

    public function generateVariants()
    {
        if (empty($this->selectedAttributes)) {
            $this->variants = [];
            return;
        }

        // Get all possible combinations
        $valueSets = [];
        foreach ($this->selectedAttributes as $attributeId) {
            $attribute = $this->productAttributes->find($attributeId);
            $valueSets[] = $attribute->values->pluck('id', 'id')->toArray();
        }

        $combinations = $this->generateCombinations($valueSets);

        // Preserve existing variant data
        $existingVariants = collect($this->variants)->keyBy(function ($variant) {
            return $this->getVariantKey($variant['attributes']);
        });

        $this->variants = [];
        foreach ($combinations as $combination) {
            $variantKey = $this->getVariantKey(array_combine($this->selectedAttributes, $combination));
            $existingVariant = $existingVariants->get($variantKey, [
                'sku' => '',
                'stock' => 0,
                'retail_price' => $this->retail_price ?? 0,
            ]);

            $this->variants[] = [
                'id' => $existingVariant['id'] ?? null,
                'attributes' => array_combine($this->selectedAttributes, $combination),
                'sku' => $existingVariant['sku'],
                'stock' => $existingVariant['stock'],
                'retail_price' => $existingVariant['retail_price'],
            ];
        }
    }

    private function generateCombinations($arrays)
    {
        $result = [[]];
        foreach ($arrays as $array) {
            $append = [];
            foreach ($result as $product) {
                foreach ($array as $item) {
                    $append[] = array_merge($product, [$item]);
                }
            }
            $result = $append;
        }
        return $result;
    }

    private function getVariantKey($attributes)
    {
        ksort($attributes);
        return implode('-', $attributes);
    }

    public function save()
    {
        $this->validate($this->rules);

        // Create or update product
        $productData = [
            'name' => $this->name,
            'supplier_id' => $this->supplier_id,
            'factory_price' => $this->factory_price,
            'distributor_price' => $this->distributor_price,
            'reseller_price' => $this->reseller_price,
        ];

        if ($this->productId) {
            $this->product->update($productData);
        } else {
            $this->product = Product::create($productData);
        }

        // Handle variants
        foreach ($this->variants as $variantData) {
            $variant = isset($variantData['id'])
                ? ProductVariant::find($variantData['id'])
                : new ProductVariant();

            if (!$variant) {
                $variant = new ProductVariant();
            }

            $variant->product_id = $this->product->id;
            $variant->sku = $variantData['sku'];
            $variant->stock = $variantData['stock'];
            $variant->retail_price = $variantData['retail_price'];
            $variant->save();

            // Sync attribute values
            $variant->attributeValues()->sync($variantData['attributes']);
        }

        // Delete removed variants
        if ($this->productId) {
            $existingVariantIds = collect($this->variants)->pluck('id')->filter()->toArray();
            ProductVariant::where('product_id', $this->product->id)
                ->whereNotIn('id', $existingVariantIds)
                ->delete();
        }

        // Save attributes and values to DB, linked to this product
        foreach ($this->productAttributes as $attrData) {
            $attribute = Attribute::create([
                'product_id' => $this->product->id,
                'name' => $attrData['name'],
            ]);
            foreach ($attrData['values'] as $value) {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }
        }

        $this->hasUnsavedChanges = false;
        $this->dispatch('product-saved');

        session()->flash('success', $this->productId
            ? __('product.messages.updated')
            : __('product.messages.created')
        );

        return redirect()->route('products.index');
    }

    public function render()
    {
        return view('livewire.product.product-form', [
            'suppliers' => Supplier::all(),
            'variants' => $this->variants,
        ]);
    }

    public function generateSkuCode($variant)
    {
        if (!empty($this->bulkSkuPrefix)) {
            return $this->bulkSkuPrefix . '-' . $this->generateSkuSuffix($variant);
        }

        $parts = [];
        $parts[] = 'sn01';
        return $parts[0] . '-' . $this->generateSkuSuffix($variant);
    }

    public function getDiscountedPrice($price)
    {
        return $price * 0.73; // 27% discount
    }

    public function updatedVariants($value, $key)
    {
        // If retail_price is updated, update the SKU
        if (str_contains($key, 'retail_price')) {
            $index = explode('.', $key)[1];
            if (!isset($this->variants[$index]['sku']) || empty($this->variants[$index]['sku'])) {
                $this->variants[$index]['sku'] = $this->generateSkuCode($this->variants[$index]);
            }
        }
    }

    public function applyBulkPrice()
    {
        if (!is_numeric($this->bulkPrice)) return;

        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['retail_price'] = $this->bulkPrice;
        }
    }

    public function applyBulkStock()
    {
        if (!is_numeric($this->bulkStock)) return;

        foreach ($this->variants as $index => $variant) {
            $this->variants[$index]['stock'] = $this->bulkStock;
        }
    }

    public function applyBulkSku()
    {
        if (empty($this->bulkSkuPrefix)) return;

        foreach ($this->variants as $index => $variant) {
            $variantSuffix = $this->generateSkuSuffix($variant);
            $this->variants[$index]['sku'] = $this->bulkSkuPrefix . '-' . $variantSuffix;
        }
    }

    private function generateSkuSuffix($variant)
    {
        $parts = [];
        foreach ($this->selectedAttributes as $attributeId) {
            $attribute = $this->productAttributes->find($attributeId);
            $value = $attribute->values->find($variant['attributes'][$attributeId] ?? null);

            if ($value) {
                $parts[] = Str::slug($value->value);
            }
        }
        return implode('-', $parts);
    }

    // Add this property watcher for selectedAttributes
    public function updatedSelectedAttributes($value)
    {
        if (count($this->selectedAttributes) > 2) {
            array_pop($this->selectedAttributes);
            session()->flash('error', __('product.messages.max_attributes'));
            return;
        }
        $this->generateVariants();
    }
}
