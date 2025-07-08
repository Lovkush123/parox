<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductMedia extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'size_id',
        'file_path',
    ];

    // Optional: Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }
}
