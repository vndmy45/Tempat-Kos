<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KosFasilitas extends Model
{
    use HasFactory;

    protected $table = 'kos_fasilitas';

    protected $fillable = [
        'id_kos', 'id_fasilitas',
    ];
}

