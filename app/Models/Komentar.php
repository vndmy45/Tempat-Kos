<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Komentar extends Model
{
    use HasFactory;

    protected $table = 'komentar';

    protected $fillable = [
        'id_user', 'id_kos', 'rating', 'isi_komentar',
    ];

    public function user()
    {
        return $this->belongsTo(DataUser::class, 'id_user');
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }
}

