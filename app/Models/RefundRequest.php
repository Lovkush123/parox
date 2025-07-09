<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefundRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'comment',
        'reason',
        'type',
        'status',
        'email',
    ];

    public function images()
    {
        return $this->hasMany(RefundRequestImage::class);
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
} 