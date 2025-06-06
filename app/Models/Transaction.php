<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_code',
        'reseller_id',
        'shipping_pdf_path',
        'shipping_number',
        'user_id',
        'tax',
        'total_paid',
        'total_price',
        'status',
        'description'
    ];

    protected $casts = [
        'tax' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function variants()
    {
        return $this->hasManyThrough(ProductVariant::class, TransactionItem::class);
    }

    public function products()
    {
        return $this->hasManyThrough(Product::class, TransactionItem::class, 'transaction_id', 'id', 'id', 'variant_id');
    }
}
