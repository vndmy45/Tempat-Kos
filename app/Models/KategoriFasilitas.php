<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriFasilitas extends Model
{
    use HasFactory;
    
    protected $table = 'kategori_fasilitas';

    protected $fillable = ['nama_kategori'];

    public function fasilitas()
    {
        return $this->hasMany(Fasilitas::class, 'id_kategori');
    }
}
