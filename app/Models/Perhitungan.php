<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Perhitungan extends Model
{
    use HasFactory;

    protected $table = 'perhitungan';

    protected $fillable = [
        'id_user', 'cosine_similarity', 'hasil_rekomendasi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}

