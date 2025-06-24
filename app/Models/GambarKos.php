<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GambarKos extends Model
{
    use HasFactory;

    protected $table = 'gambar_kos';

    protected $fillable = [
        'nama_foto', 'id_kos', 'link_foto',
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }
}

