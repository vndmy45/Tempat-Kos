<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NormalisasiKos extends Model
{
    use HasFactory;
     protected $table = 'normalisasi_kos';

    protected $fillable = [
        'id_kos', 'harga_normalized', 'rating_normalized', 'jarak_normalized', 'fasilitas_normalized'
    ];

    protected $casts = [
        'fasilitas_normalized' => 'array'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }
}
