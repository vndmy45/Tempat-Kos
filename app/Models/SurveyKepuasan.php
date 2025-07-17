<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SurveyKepuasan extends Model
{
    use HasFactory;

    protected $table = 'survey_kepuasan';

    protected $fillable = [
        'id_user', 'id_kos', 'skor', 'komentar',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'id_kos');
    }
}

