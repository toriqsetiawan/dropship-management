<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'size_chart',
        'weight',
        'dimensions',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function attributeCombinations()
    {
        return $this->hasMany(ProductAttributeCombination::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function getPriceForRole($roleId)
    {
        return $this->prices()->where('role_id', $roleId)->first();
    }

    public function getPrimaryImage()
    {
        return $this->images()->where('is_primary', true)->first();
    }
}
