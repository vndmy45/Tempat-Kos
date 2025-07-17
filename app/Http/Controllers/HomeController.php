<?php

namespace App\Http\Controllers;

use App\Models\Kos;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    public function index()
{
    $rekomendasiKos = Kos::with('gambarKos')
        ->where('nilai_rating', '>=', 4.5) 
        ->orderByDesc('nilai_rating')// jumlah yang ditampilkan
        ->take(4) 
        ->get();

    return view('homeuser', compact('rekomendasiKos'));
}
}
