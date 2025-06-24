<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataUser extends Model
{
    use HasFactory;

    protected $table = 'data_user';

    protected $fillable = [
        'nama_user', 'alamat', 'email', 'username', 'password', 'role',
    ];

    public function komentar()
    {
        return $this->hasMany(Komentar::class, 'id_user');
    }

    public function survey()
    {
        return $this->hasMany(SurveyKepuasan::class, 'id_user');
    }

    public function perhitungan()
    {
        return $this->hasMany(Perhitungan::class, 'id_user');
    }
}
