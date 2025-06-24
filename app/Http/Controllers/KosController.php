<?php

namespace App\Http\Controllers;

use App\Models\Kos;
use App\Http\Requests\StoreKosRequest;
use App\Http\Requests\UpdateKosRequest;
use Illuminate\Http\Request;

class KosController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('search');

        $dataKos = Kos::when($keyword, function ($query) use ($keyword) {
            $query->where('nama_kos', 'like', "%{$keyword}%")
                ->orWhere('alamat', 'like', "%{$keyword}%")
                ->orWhere('jenis_kost', 'like', "%{$keyword}%");
        })->paginate(5);

        return view('admin.kos.index', compact('dataKos', 'keyword'));
    }

    public function create()
    {
        return view('admin.kos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kos' => 'required',
            'alamat' => 'required',
            'harga' => 'required|numeric',
            'jenis_kost' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
            'nilai_rating' => 'nullable|numeric|min:0|max:5',
            'kontak_pemilik' => 'required',
        ]);
        Kos::create($request->all());

        return redirect()->route('kos.index')->with('success', 'Data kos berhasil ditambahkan.');
    }

    public function show(Kos $kos)
    {
        return view('admin.kos.show', compact('kos'));
    }

    public function edit(Kos $kos)
    {
        return view('admin.kos.edit', compact('kos'));
    }

    public function update(Request $request, Kos $kos)
    {
    $request->validate([
        'nama_kos' => 'required',
        'alamat' => 'required',
        'harga' => 'required|numeric',
        'jenis_kost' => 'required',
        'longitude' => 'required',
        'latitude' => 'required',
        'nilai_rating' => 'nullable|numeric|min:0|max:5',
        'kontak_pemilik' => 'required',
    ]);

    $kos->update($request->all());

    return redirect()->route('kos.index')->with('success', 'Data kos berhasil diupdate.');
    }

    public function destroy(Kos $kos)
    {
        $kos->delete();
        return redirect()->route('kos.index')->with('success', 'Data kos berhasil dihapus.');
    }
}

