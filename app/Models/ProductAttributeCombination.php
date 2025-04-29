<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttributeCombination extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_value_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    public function value()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'attribute_value_id');
    }
}
