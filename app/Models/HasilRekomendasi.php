<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilRekomendasi extends Model
{
    use HasFactory;

    protected $table = 'hasil_rekomendasi';

    protected $fillable = [
        'id_user',
        'id_kos',
        'nilai_similarity'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
