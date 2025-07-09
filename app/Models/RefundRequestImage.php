<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefundRequestImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'refund_request_id',
        'image_path',
    ];

    public function refundRequest()
    {
        return $this->belongsTo(RefundRequest::class);
    }
} 