<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
     use HasFactory;

   protected $fillable = [
    'user_id',
    'address_id',
    'unique_order_id',
    'order_status',
    'delivery_status',
    'payment_status',
    'payment_response_id',
    'subtotal',
    'tax',
    'total',
    'payment_type',
    ];
}
