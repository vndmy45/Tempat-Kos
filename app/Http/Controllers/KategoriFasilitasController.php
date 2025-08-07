<?php

namespace App\Http\Controllers;

use App\Models\KategoriFasilitas;
use Illuminate\Http\Request;

class KategoriFasilitasController extends Controller
{
    public function index()
    {
        $kategori = KategoriFasilitas::paginate(10);
        return view('admin.kategori_fasilitas.index', compact('kategori'));
    }

    public function store(Request $request)
    {
        $request->validate(['nama_kategori' => 'required|string|max:255']);
        KategoriFasilitas::create($request->all());

        return redirect()->route('kategori-fasilitas.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
{
    $request->validate(['nama_kategori' => 'required|string|max:255']);
    
    $kategori = KategoriFasilitas::findOrFail($id);
    $kategori->update($request->all());

    return redirect()->route('kategori-fasilitas.index')->with('success', 'Kategori berhasil diperbarui.');
}


    public function destroy($id)
{
    $kategori = KategoriFasilitas::findOrFail($id);

    if ($kategori->fasilitas()->count() > 0) {
        return redirect()->back()->with('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh fasilitas.');
    }

    $kategori->delete();
    return redirect()->route('kategori-fasilitas.index')->with('success', 'Kategori berhasil dihapus.');
}

}