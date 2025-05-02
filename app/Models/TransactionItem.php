<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'variant_id',
        'quantity',
        'factory_price',
        'distributor_price',
        'reseller_price',
        'retail_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'factory_price' => 'decimal:2',
        'distributor_price' => 'decimal:2',
        'reseller_price' => 'decimal:2',
        'retail_price' => 'decimal:2'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function product()
    {
        return $this->hasOneThrough(Product::class, ProductVariant::class, 'id', 'id', 'variant_id', 'product_id');
    }
}
