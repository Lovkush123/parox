<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_one',
        'address_two',
        'city',
        'state',
        'pincode',
        'address_type',
    ];

    // Optional relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
