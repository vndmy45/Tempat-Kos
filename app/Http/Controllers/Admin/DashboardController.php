<?php

namespace App\Http\Controllers\Admin;
use App\Models\Kos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
    // Ambil semua data kos
    $semuaKos = Kos::all();

    // Dapatkan rentang harga minimum dan maksimum
    $hargaMin = $semuaKos->min('harga');
    $hargaMax = $semuaKos->max('harga');

    // Jumlah total kos
    $jumlahKos = $semuaKos->count();

    // Jumlah berdasarkan jenis kost
    $jumlahPutra = $semuaKos->where('jenis_kost', 'Putra')->count();
    $jumlahPutri = $semuaKos->where('jenis_kost', 'Putri')->count();
    $jumlahCampur = $semuaKos->where('jenis_kost', 'Campur')->count();

    // Hitung jumlah kos dalam rentang harga tertentu untuk chart
    $range1 = Kos::whereBetween('harga', [0, 450000])->count();
    $range2 = Kos::whereBetween('harga', [451000, 650000])->count();
    $range3 = Kos::whereBetween('harga', [651000, 1000000])->count(); // sesuaikan max range

    // Data untuk grafik jenis kos
    $chartJenisKos = [
        'Putra' => $jumlahPutra,
        'Putri' => $jumlahPutri,
        'Campur' => $jumlahCampur,
    ];
    return view('admin.dashboard', [
        'hargaMin' => $hargaMin,
        'hargaMax' => $hargaMax,
        'jumlahKos' => $jumlahKos,
        'chartData' => [
            $range1,
            $range2,
            $range3
        ], //untuk grafik rentang harga
        'chartJenisKos' => [
        'Putra' => $jumlahPutra,
        'Putri' => $jumlahPutri,
        'Campur' => $jumlahCampur
    ]
    ]);
    }

}
