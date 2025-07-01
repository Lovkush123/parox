<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

   protected $fillable = [
    'size',
    'price',
    'mrp',
    'selling',
    'cod',
    'total_stock',
    'stock_status',
    'length',
    'width',
    'height',
    'weight',
    'product_id',
];
}
