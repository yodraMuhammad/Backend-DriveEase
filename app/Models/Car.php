<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'license_plate',
        'rental_rate',
        'available',
        'owner_id',
        'photo',
    ];

    /**
     * Get the user that owns the car.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}
