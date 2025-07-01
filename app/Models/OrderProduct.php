<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;

    protected $table = 'order_product';

    protected $fillable = [
        'user_id',
        'address_id',
        'product_id',
        'size_id',
        'subtotal',
        'tax',
        'total',
    ];

    // Optional: define relationships
    // public function user() {
    //     return $this->belongsTo(User::class);
    // }

    // public function product() {
    //     return $this->belongsTo(Product::class);
    // }
}
