<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReviewImage extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'product_review_id',
        'image_path',
    ];

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }
} 