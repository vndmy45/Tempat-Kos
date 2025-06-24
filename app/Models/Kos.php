<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kos extends Model
{
    use HasFactory;

    protected $table = 'kos';

   protected $fillable = [
    'nama_kos', 'alamat', 'harga', 'jenis_kost','longitude', 'latitude', 'nilai_rating', 'kontak_pemilik'
    ];


    public function gambarKos()
    {
        return $this->hasMany(GambarKos::class, 'id_kos');
    }

    public function komentar()
    {
        return $this->hasMany(Komentar::class, 'id_kos');
    }

    public function survey()
    {
        return $this->hasMany(SurveyKepuasan::class, 'id_kos');
    }

    public function fasilitas()
    {
        return $this->belongsToMany(Fasilitas::class, 'kos_fasilitas', 'id_kos', 'id_fasilitas')->withPivot('nilai_fasilitas');
    }
}


