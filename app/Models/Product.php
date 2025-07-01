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
        'note', // ✅ Added note field here
    ];

    // Relationship to Category (optional, if you have a Category model)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
