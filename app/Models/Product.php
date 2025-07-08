<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

   protected $fillable = [
    'name',
    'description',
    'image',
    'category_id',
    'note',
    'brand',
    'slug',
    'tagline',
    'heart_notes',
    'top_notes',
    'base_notes',
    'features',
    'gender',
    'status', // if you want to mass assign status as well
    'type',
];
    // Relationship to Category (optional, if you have a Category model)
   // Product.php

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function sizes()
    {
        return $this->hasMany(Size::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function reviews(){
        return $this->hasMany(ProductReview::class)->with(["user", "images"]);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_product');
    }
}
