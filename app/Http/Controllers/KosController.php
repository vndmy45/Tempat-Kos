<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\GambarKos;
use App\Models\KategoriFasilitas;
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
    // Menampilkan daftar kos ke admin dengan fitur pencarian
    public function index(Request $request)
    {
        $keyword = $request->input('search');

        // Ambil data kos, termasuk relasi gambar dan fasilitas
        $dataKos = Kos::with('gambarKos', 'fasilitas')
            ->when($keyword, function ($query) use ($keyword) {
                // Filter berdasarkan nama, alamat, atau jenis kos
                $query->where('nama_kos', 'like', "%{$keyword}%")
                    ->orWhere('alamat', 'like', "%{$keyword}%")
                    ->orWhere('jenis_kost', 'like', "%{$keyword}%");
            })->paginate(5); // Paginasi 5 data per halaman

        $dataFasilitas = Fasilitas::all();

        return view('admin.kos.index', compact('dataKos', 'keyword', 'dataFasilitas'));
    }

    // Menampilkan form tambah kos
    public function create()
    {
        return view('admin.kos.create');
    }

    // Menyimpan data kos baru ke database
    public function store(Request $request)
    {
        // Validasi input
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
            'fasilitas' => 'nullable|array',
            'fasilitas.*' => 'exists:fasilitas,id'
        ]);

        // Simpan data utama kos
        $kos = Kos::create([
            'nama_kos' => $request->nama_kos,
            'alamat' => $request->alamat,
            'harga' => $request->harga,
            'jenis_kost' => $request->jenis_kost,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'nilai_rating' => $request->nilai_rating,
            'kontak_pemilik' => $request->kontak_pemilik,
        ]);

        if ($request->filled('fasilitas')) {
            $kos->fasilitas()->sync($request->fasilitas);
        }

        // Simpan gambar-gambar yang diunggah
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

        // Simpan nilai normalisasi untuk data kos
        $this->simpanNormalisasi($kos);

        return redirect()->route('kos.index')->with('success', 'Data kos berhasil ditambahkan.');
    }

    // Menampilkan detail kos ke admin
    public function show(Kos $kos)
{
    $kos->load('fasilitas', 'gambarKos', 'komentar.user');

    $kategoriFasilitas = KategoriFasilitas::with([
        'fasilitas' => function ($query) use ($kos) {
            $query->whereIn('id', $kos->fasilitas->pluck('id'));
        }
    ])->get();

    return view('admin.kos.show', compact('kos', 'kategoriFasilitas'));
}

    // Menampilkan form edit kos
    public function edit(Kos $kos)
    {
        return view('admin.kos.edit', compact('kos'));
    }

    // Memperbarui data kos
    public function update(Request $request, Kos $kos)
    {
        // Validasi input
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

        if ($request->has('fasilitas')) {
            $kos->fasilitas()->sync($request->fasilitas);
        } else {
            $kos->fasilitas()->sync([]); // Jika user menghapus semua fasilitas
        }

        // Update gambar jika ada yang diunggah baru
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

    // Menghapus data kos
    public function destroy(Kos $kos)
    {
        $kos->delete();
        return redirect()->route('kos.index')->with('success', 'Data kos berhasil dihapus.');
    }

    // Menampilkan detail kos ke user
    public function showUserKos($id)
    {
        $kos = Kos::with(['gambarKos', 'fasilitas', 'komentar.user'])->findOrFail($id);

        // Hitung jarak jika ada parameter, atau gunakan fungsi default
        if (request()->has('jarak')) {
            $kos->jarak = (float) request()->jarak;
        } else {
            $kos->jarak = round($this->hitungJarak($kos->latitude, $kos->longitude), 2);
        }

        // Simpan halaman asal jika ada, untuk navigasi balik
        if (request()->has('from')) {
            session(['kos_back_url' => request()->query('from')]);
        }

        $kategoriFasilitas = KategoriFasilitas::with([
            'fasilitas' => function ($query) use ($kos) {
                $query->whereIn('id', $kos->fasilitas->pluck('id'));
            }
        ])->get();

return view('detailkos', compact('kos', 'kategoriFasilitas'));
    }

    // Menyimpan komentar dan rating dari user terhadap kos
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

        // Hitung ulang nilai rating
        $kos = Kos::findOrFail($request->id_kos);
        $rataRating = Komentar::where('id_kos', $request->id_kos)->avg('rating');
        $kos->nilai_rating = $rataRating;
        $kos->save();

        // Simpan ulang normalisasi karena nilai rating berubah
        $this->simpanNormalisasi($kos);

        return redirect()->back()->with('success', 'Komentar berhasil dikirim!');
    }

    // Fungsi untuk menyimpan nilai normalisasi dari kos
    private function simpanNormalisasi(Kos $kos)
    {
    // Normalisasi harga (0: murah, 0.5: sedang, 1: mahal)
    $harga = $kos->harga < 500000 ? 0 : ($kos->harga > 1000000 ? 1 : 0.5);

    // Normalisasi rating (dibagi 5)
    $rating = $kos->nilai_rating ? $kos->nilai_rating / 5 : 0.5;

    // Normalisasi jarak
    $jarak = $this->hitungJarak($kos->latitude, $kos->longitude);
    $jarakNormalized = $jarak < 1 ? 0 : ($jarak > 3 ? 1 : 0.5);

    // Ambil semua fasilitas yang tersedia di DB (dengan index)
    $allFasilitas = Fasilitas::orderBy('index')->get(['id', 'index']);
    $kosFasilitas = $kos->fasilitas->pluck('id')->toArray();

    // Normalisasi fasilitas: 1 jika kos punya fasilitas tsb, 0 jika tidak
    $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($kosFasilitas) {
    return in_array($fasilitas->id, $kosFasilitas) ? 1 : 0;
    })->toArray();

    // Simpan hasil ke tabel normalisasi_kos
    NormalisasiKos::updateOrCreate(
    ['id_kos' => $kos->id],
    [
        'harga_normalized' => $harga,
        'rating_normalized' => $rating,
        'jarak_normalized' => $jarakNormalized,
        'fasilitas_normalized' => $fasilitasVector, // hasil array dengan panjang = jumlah fasilitas
    ]
    );
    }


    // Fungsi menghitung jarak antara titik preferensi dengan kos
    private function hitungJarak($lat2, $lon2)
    {
        $lat1 = -8.295814841001143; // titik referensi
        $lon1 = 114.3076786627924;
        $earth_radius = 6371; // dalam KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }
}
