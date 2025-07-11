<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProducts extends Model
{
    use SoftDeletes;
    use HasFactory;

   protected $fillable = [
    'order_id',
    'product_id',
    'size_id',
    'quantity',
    'price',
    ];

      public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 🔁 Relation to Product
    public function product()
    {
        return $this->belongsTo(Product::class, "product_id")->with(["images", 'reviews']);
    }

    // 🔁 Relation to Size
    public function size()
    {
        return $this->belongsTo(Size::class, "size_id");
    }
}
