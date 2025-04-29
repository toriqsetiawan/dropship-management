<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    public function combinations()
    {
        return $this->hasMany(ProductAttributeCombination::class);
    }
}
