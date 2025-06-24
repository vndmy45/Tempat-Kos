<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use Illuminate\Http\Request;

class FasilitasController extends Controller
{
    public function index()
    {
    $dataFasilitas = Fasilitas::paginate(5); // atau jumlah item per halaman yang kamu mau
    return view('admin.fasilitas.index', compact('dataFasilitas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_fasilitas' => 'required|string|max:255',
            'index' => 'required|integer',
        ]);

        Fasilitas::create($request->all());

        return redirect()->route('fasilitas.index')->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function update(Request $request, Fasilitas $fasilitas)
    {
        $request->validate([
            'nama_fasilitas' => 'required|string|max:255',
            'index' => 'required|integer',
        ]);

        $fasilitas->update($request->all());

        return redirect()->route('fasilitas.index')->with('success', 'Fasilitas berhasil diupdate.');
    }

    public function destroy(Fasilitas $fasilitas)
    {
        $fasilitas->delete();
        return redirect()->route('fasilitas.index')->with('success', 'Fasilitas berhasil dihapus.');
    }
}
