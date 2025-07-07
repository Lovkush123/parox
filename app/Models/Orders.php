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


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function address() {
        return $this->belongsTo(Address::class);
    }

     public function products()
    {
        return $this->hasMany(OrderProducts::class, 'order_id')->with(["product", "size"]);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
