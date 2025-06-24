<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private function getFiturData()
    {
        return [
            [
                'image' => 'Fitur1.png',
                'title' => 'Fasilitas',
                'type' => 'Rumah minimalis Type-A2',
                'price' => 'IDR.200jt'
            ],
            [
                'image' => 'Fitur1.png',
                'title' => 'Kamar Tidur',
                'type' => 'Rumah minimalis Type-A2',
                'price' => 'IDR.200jt'
            ],
            [
                'image' => 'Fitur1.png',
                'title' => 'Kamar Tidur',
                'type' => 'Rumah minimalis Type-A2',
                'price' => 'IDR.200jt'
            ],
            [
                'image' => 'Fitur1.png',
                'title' => 'Kamar Tidur',
                'type' => 'Rumah minimalis Type-A2',
                'price' => 'IDR.200jt'
            ]
        ];
    }

    public function index()
    {
        $fiturData = $this->getFiturData();
        return view('home', compact('fiturData'));
    }
}
