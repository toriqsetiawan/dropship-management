<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'sku',
        'name',
        'image',
        'size',
        'factory_price',
        'distributor_price',
        'reseller_price',
    ];

    protected $casts = [
        'factory_price' => 'decimal:2',
        'distributor_price' => 'decimal:2',
        'reseller_price' => 'decimal:2',
    ];

    protected $appends = ['image_url'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function transactionItems()
    {
        return $this->hasManyThrough(TransactionItem::class, ProductVariant::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/products/' . $this->image)
            : asset('images/placeholder.png');
    }

}
