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

}
