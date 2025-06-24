<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fasilitas extends Model
{
    use HasFactory;

    protected $table = 'fasilitas';

    protected $fillable = [
        'nama_fasilitas', 'index',
    ];

    public function kos()
    {
        return $this->belongsToMany(Kos::class, 'kos_fasilitas', 'id_fasilitas', 'id_kos')->withPivot('nilai_fasilitas');
    }
}

