<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderList extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'order_list';

    protected $fillable = [
        'order_product_id',
        'order_id',
        'subtotal',
        'tax',
        'total',
        'response_id',
        'order_status',
        'tracking_id',
        'payment_type',
    ];

    // Optional: Define relationships if needed
    // public function orderProduct()
    // {
    //     return $this->belongsTo(OrderProduct::class);
    // }
}

