<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'role_id',
        'buy_price',
        'sell_price',
        'reseller_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
