<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\Kos;
use Illuminate\Http\Request;

class PencarianController extends Controller
{
    public function index(Request $request)
{
    $fasilitas = Fasilitas::all();

    $kos = Kos::with('fasilitas')
        ->when($request->harga, function ($query) use ($request) {
            if ($request->harga === '< Rp. 500.000') {
                $query->where('harga', '<', 500000);
            } elseif ($request->harga === 'Rp. 500.000 - Rp. 1.000.000') {
                $query->whereBetween('harga', [500000, 1000000]);
            } elseif ($request->harga === '> Rp. 1.000.000') {
                $query->where('harga', '>', 1000000);
            }
        })
        ->when($request->rating, function ($query) use ($request) {
            $query->where('rating', '>=', $request->rating);
        })
        ->when($request->jarak, function ($query) use ($request) {
            if ($request->jarak === '< 1 km') {
                $query->where('jarak', '<', 1);
            } elseif ($request->jarak === '1 - 3 km') {
                $query->whereBetween('jarak', [1, 3]);
            } elseif ($request->jarak === '> 3 km') {
                $query->where('jarak', '>', 3);
            }
        })
        ->when($request->filled('fasilitas'), function ($query) use ($request) {
        $query->whereHas('fasilitas', function ($subQuery) use ($request) {
        $subQuery->whereIn('fasilitas.id', $request->fasilitas);
        });
        })
        ->latest()
        ->paginate(9);

    return view('pencarian', [
        'kos_list' => $kos,
        'fasilitas' => $fasilitas
    ]);
}

}
