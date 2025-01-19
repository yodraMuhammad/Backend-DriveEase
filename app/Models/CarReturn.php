<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarReturn extends Model
{
    use HasFactory;

    protected $table = 'car_returns'; // Nama tabel jika tidak mengikuti konvensi default

    protected $fillable = [
        'rental_id',
        'return_date',
        'days_rented',
        'total_cost',
    ];

    /**
     * Relasi ke tabel rentals (satu return terkait dengan satu rental).
     */
    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}
