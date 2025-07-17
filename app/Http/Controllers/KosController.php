<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\GambarKos;
use App\Models\Komentar;
use App\Models\Kos;
use App\Http\Requests\StoreKosRequest;
use App\Http\Requests\UpdateKosRequest;
use App\Models\KosFasilitas;
use App\Models\NormalisasiKos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KosController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('search');

        $dataKos = Kos::with('gambarKos', 'fasilitas') 
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('nama_kos', 'like', "%{$keyword}%")
                    ->orWhere('alamat', 'like', "%{$keyword}%")
                    ->orWhere('jenis_kost', 'like', "%{$keyword}%");
            })->paginate(5);

            $dataFasilitas = Fasilitas::all();

        return view('admin.kos.index', compact('dataKos', 'keyword', 'dataFasilitas'));
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
            'gambar.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'fasilitas' => 'nullable|array'
        ]);
        // Simpan kos dulu dan ambil objeknya
        $kos = Kos::create([
            'nama_kos' => $request->nama_kos,
            'alamat' => $request->alamat,
            'harga' => $request->harga,
            'jenis_kost' => $request->jenis_kost,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'nilai_rating' => $request->nilai_rating,
            'kontak_pemilik' => $request->kontak_pemilik,
            'fasilitas.*' => 'exists:fasilitas,id',
        ]);

        // Simpan gambar-gambarnya
        if ($request->hasFile('gambar')) {
            foreach ($request->file('gambar') as $file) {
                $originalName = $file->getClientOriginalName();
                $path = $file->store('gambar_kos', 'public');

                $link = asset('storage/' . $path); // hasil: http://localhost:8000/storage/gambar_kos/namafile.jpg

                GambarKos::create([
                    'id_kos' => $kos->id,
                    'gambar' => $path,
                    'nama_foto' => $originalName,
                    'link_foto' => $link
                ]);
            }
        }
        // Simpan relasi fasilitas (jika dipilih)
        if ($request->has('fasilitas')) {
            $data = [];
            foreach ($request->fasilitas as $idFasilitas) {
                $data[$idFasilitas] = ['nilai_fasilitas' => 1]; // kamu bisa ganti 1 jadi nilai dinamis
            }
            $kos->fasilitas()->sync($data);
        }
        $this->simpanNormalisasi($kos);


        return redirect()->route('kos.index')->with('success', 'Data kos berhasil ditambahkan.');
    }

    public function show(Kos $kos)
    {
        $kos->load('fasilitas','gambarKos', 'komentar.user'); // load relasi
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
        'gambar.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    // Update data kos
    $kos->update([
        'nama_kos' => $request->nama_kos,
        'alamat' => $request->alamat,
        'harga' => $request->harga,
        'jenis_kost' => $request->jenis_kost,
        'longitude' => $request->longitude,
        'latitude' => $request->latitude,
        'nilai_rating' => $request->nilai_rating,
        'kontak_pemilik' => $request->kontak_pemilik,
    ]);

    // Update fasilitas
    $syncData = [];
    if ($request->has('fasilitas')) {
        foreach ($request->fasilitas as $fasilitasId) {
            $syncData[$fasilitasId] = ['nilai_fasilitas' => 0];
        }
        $kos->fasilitas()->sync($syncData);
    } else {
        $kos->fasilitas()->detach();
    }
    
    if ($request->hasFile('gambar')) {
        foreach ($request->file('gambar') as $file) {
            $originalName = $file->getClientOriginalName();
            $path = $file->store('gambar_kos', 'public');

            $link = asset('storage/' . $path);

            GambarKos::create([
                'id_kos' => $kos->id,
                'gambar' => $path,
                'nama_foto' => $originalName,
                'link_foto' => $link
            ]);
        }
    }

    // Perbarui normalisasi
    $this->simpanNormalisasi($kos);

    return redirect()->route('kos.index')->with('success', 'Data kos berhasil diupdate.');
    }


    public function destroy(Kos $kos)
    {
        $kos->delete();
        return redirect()->route('kos.index')->with('success', 'Data kos berhasil dihapus.');
    }

    public function showUserKos($id)
    {
        $kos = Kos::with(['gambarKos', 'fasilitas', 'komentar.user'])->findOrFail($id);
        
        if (request()->has('jarak')) {
            $kos->jarak = (float) request()->jarak;
        } else {
            $kos->jarak = round($this->hitungJarak($kos->latitude, $kos->longitude), 2);
        }

        if (request()->has('from')) {
            session(['kos_back_url' => request()->query('from')]);
        }

        return view('detailkos', compact('kos'));
    }
    
    public function simpanKomentar(Request $request)
    {
        $request->validate([
            'id_kos' => 'required|exists:kos,id',
            'rating' => 'required|numeric|min:1|max:5',
            'isi_komentar' => 'required|string',
        ]);

        Komentar::create([
            'id_user' => Auth::id(),
            'id_kos' => $request->id_kos,
            'rating' => $request->rating,
            'isi_komentar' => $request->isi_komentar,
        ]);

        // Hitung ulang rata-rata rating kos
        $kos = Kos::findOrFail($request->id_kos);
        $rataRating = Komentar::where('id_kos', $request->id_kos)->avg('rating');
        $kos->nilai_rating = $rataRating;
        $kos->save();

        // Update normalisasi termasuk rating
        $this->simpanNormalisasi($kos);

        return redirect()->back()->with('success', 'Komentar berhasil dikirim!');
    }

        private function simpanNormalisasi(Kos $kos)
    {
        $harga = $kos->harga < 500000 ? 0 : ($kos->harga > 1000000 ? 1 : 0.5);
        $rating = $kos->nilai_rating ? $kos->nilai_rating / 5 : 0.5;
        $jarak = $this->hitungJarak($kos->latitude, $kos->longitude);
        $jarakNormalized = $jarak < 1 ? 0 : ($jarak > 3 ? 1 : 0.5);

        // Ambil semua fasilitas (urut berdasarkan index)
        $allFasilitas = Fasilitas::orderBy('index')->get(['id', 'index']);
        $maxIndex = $allFasilitas->max('index'); // ← ambil nilai maksimum index

        // Ambil id fasilitas yang dimiliki oleh kos
        $kosFasilitas = $kos->fasilitas->pluck('id')->toArray();

        // Buat vektor fasilitas dengan index yang dinormalisasi
        $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($kosFasilitas, $maxIndex) {
            return in_array($fasilitas->id, $kosFasilitas)
                ? $fasilitas->index / $maxIndex
                : 0;
        })->toArray();

        NormalisasiKos::updateOrCreate([
            'id_kos' => $kos->id
        ], [
            'harga_normalized' => $harga,
            'rating_normalized' => $rating,
            'jarak_normalized' => $jarakNormalized,
            'fasilitas_normalized' => $fasilitasVector
        ]);
    }
    private function hitungJarak($lat2, $lon2)
{
    $lat1 = -8.295814841001143;
    $lon1 = 114.3076786627924;
    $earth_radius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}

}

