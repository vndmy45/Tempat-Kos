<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use App\Models\KategoriFasilitas;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PencarianController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil semua data fasilitas dari database
        $kategoriFasilitas = KategoriFasilitas::with('fasilitas')->get();

        // Mengambil nilai rating unik dari data kos untuk keperluan filter di tampilan
        $ratings = Kos::selectRaw('FLOOR(nilai_rating) as rating')
            ->whereNotNull('nilai_rating')
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->pluck('rating');

        // Jika user memilih metode rekomendasi, maka sistem jalankan proses rekomendasi
        if ($request->metode === 'rekomendasi') {
            $kos_list = $this->prosesRekomendasi($request);
            return view('pencarian', [
                'kos_list' => $kos_list,
                'fasilitas' => $kategoriFasilitas,
                'ratings' => $ratings,
            ]);
        }

        // Jika tidak menggunakan rekomendasi, sistem jalankan pencarian manual dengan filter

        // Koordinat referensi pusat pencarian ( kampus Poliwangi)
        $latitudeRef = -8.295807953836674;
        $longitudeRef = 114.30768351352297;

        // Query pencarian kos dengan perhitungan jarak menggunakan Haversine Formula
        $kos = Kos::selectRaw("kos.*, (
                6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )
            ) AS jarak", [$latitudeRef, $longitudeRef, $latitudeRef])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['fasilitas', 'gambarKos']) // Memuat relasi fasilitas dan gambar kos

            // Filter berdasarkan harga
            ->when($request->harga, function ($query) use ($request) {
                if ($request->harga === '< Rp. 500.000') {
                    $query->where('harga', '<', 500000);
                } elseif ($request->harga === 'Rp. 500.000 - Rp. 1.000.000') {
                    $query->whereBetween('harga', [500000, 1000000]);
                } elseif ($request->harga === '> Rp. 1.000.000') {
                    $query->where('harga', '>', 1000000);
                }
            })

            // Filter berdasarkan rating minimal
            ->when($request->rating, function ($query) use ($request) {
                $query->where('nilai_rating', '>=', $request->rating);
            })

            // Filter berdasarkan jarak
            ->when($request->jarak, function ($query) use ($request) {
                if ($request->jarak === '< 1 km') {
                    $query->havingRaw('jarak < ?', [1]);
                } elseif ($request->jarak === '1 - 3 km') {
                    $query->havingRaw('jarak BETWEEN ? AND ?', [1, 3]);
                } elseif ($request->jarak === '> 3 km') {
                    $query->havingRaw('jarak > ?', [3]);
                }
            })

            // Filter berdasarkan fasilitas
            ->when($request->has('fasilitas') && is_array($request->fasilitas), function ($query) use ($request) {
                $query->whereHas('fasilitas', function ($subQuery) use ($request) {
                    $subQuery->whereIn('fasilitas.id', $request->fasilitas);
                });
            })

            ->orderBy('jarak') // Urutkan hasil berdasarkan jarak terdekat
            ->paginate(9)     // Tampilkan 9 hasil per halaman
            ->appends($request->all()); // Menyertakan query string pada pagination

        return view('pencarian', [
            'kos_list' => $kos,
            'fasilitas' => $kategoriFasilitas,
            'ratings' => $ratings,
        ]);
    }

    // Fungsi untuk memproses rekomendasi berbasis cosine similarity
    private function prosesRekomendasi(Request $request)
    {
        $user = auth()->user(); // Ambil data user yang sedang login

        $userVector = $this->buildUserVector($request); // Bangun vektor preferensi pengguna

        $dataNormalisasi = NormalisasiKos::with('kos.gambarKos')->get(); // Ambil data kos yang telah dinormalisasi
        $rekomendasi = [];

        // Lakukan iterasi pada setiap data kos
        foreach ($dataNormalisasi as $data) {
            // Ambil rata-rata skor survey dari pengguna lain terhadap kos ini
            $skorSurvey = SurveyKepuasan::where('id_kos', $data->id_kos)->avg('skor') ?? 2.5;
            $skorSurveyNormalized = $skorSurvey / 5;

            $fasilitasKos = $data->fasilitas_normalized ?? [];

            // Gabungkan seluruh atribut ke dalam satu vektor kos
            // Urutan: [harga, rating, jarak, fasilitas..., survey]
            $kosVector = array_merge([
                $data->harga_normalized,
                $data->rating_normalized,
                $data->jarak_normalized,
        ], $fasilitasKos, [$skorSurveyNormalized]);

            // Hitung nilai similarity antara user dan kos
            $similarity = $this->cosineSimilarity($userVector, $kosVector);

            // Simpan hasil similarity ke tabel hasil_rekomendasi
            HasilRekomendasi::updateOrCreate([
                'id_user' => $user->id,
                'id_kos' => $data->id_kos,
            ], [
                'nilai_similarity' => $similarity
            ]);

            // Gunakan nilai similarity yang dihitung real-time untuk tampilan yang akurat
            $data->kos->similarity = $similarity;
            $data->kos->jarak = $this->hitungJarak($data->kos->latitude, $data->kos->longitude);

            // Masukkan ke array rekomendasi
            $rekomendasi[] = $data->kos;
        }

        $topN = 8; // Ambil 8 data kos dengan similarity tertinggi

        return $this->paginateCollection(
            collect($rekomendasi)
                ->sortByDesc('similarity')
                ->take($topN)
                ->values()
        );
    }

    // Fungsi untuk melakukan paginasi manual pada koleksi
    private function paginateCollection(Collection $items, $perPage = 9)
    {
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // Fungsi perhitungan cosine similarity antara 2 vektor (user dan kos) dengan precision tinggi
    private function cosineSimilarity(array $vectorA, array $vectorB)
{
    $length = min(count($vectorA), count($vectorB)); // pastikan loop hanya sepanjang vektor terpendek

    $dotProduct = 0.0;
    $magnitudeA = 0.0;
    $magnitudeB = 0.0;

    for ($i = 0; $i < $length; $i++) {
        $dotProduct += (float)$vectorA[$i] * (float)$vectorB[$i];
        $magnitudeA += pow((float)$vectorA[$i], 2);
        $magnitudeB += pow((float)$vectorB[$i], 2);
    }

    if ($magnitudeA == 0 || $magnitudeB == 0) {
        return 0.0;
    }

    return (float)($dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB)));
}


    // Fungsi untuk membentuk vektor preferensi pengguna dari input form pencarian dengan precision tinggi
    private function buildUserVector(Request $request)
    {
        // Normalisasi harga berdasarkan pilihan pengguna dengan precision tinggi
        // Semakin kecil harga, semakin besar nilai (1 = terbaik)
        $harga = match($request->harga) {
            '< Rp. 500.000' => 1.0,    // Harga < 500k = 1 (terbaik)
            'Rp. 500.000 - Rp. 1.000.000' => 0.5,  // Harga 500k-1M = 0.5 (sedang)
            '> Rp. 1.000.000' => 0.0,  // Harga > 1M = 0 (terburuk)
            default => 0.5
        };

        // Normalisasi rating jika tersedia dengan precision tinggi
        $rating = ($request->filled('rating') ? (float)$request->rating / 5.0 : 0.5);

        // Normalisasi jarak berdasarkan input dengan precision tinggi
        // Semakin kecil jarak, semakin besar nilai (1 = terbaik)
        $jarak = match($request->jarak) {
            '< 1 km' => 1.0,      // Jarak < 1 km = 1 (terbaik)
            '1 - 3 km' => 0.5,  // Jarak 1-3 km = 0.5 (sedang)
            '> 3 km' => 0.0,      // Jarak > 3 km = 0 (terburuk)
            default => 0.5
        };

        // Ambil semua fasilitas dan bangun vektor fasilitas berdasarkan input dengan precision tinggi
        // Gunakan ID untuk konsistensi dengan database
        $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
        $userFasilitas = $request->filled('fasilitas') ? $request->fasilitas : [];

        $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($userFasilitas) {
            return in_array($fasilitas->id, $userFasilitas) ? 1.0 : 0.0;
        })->toArray();

        // Normalisasi skor survey pengguna dengan precision tinggi
        $survey = ($request->filled('survey') ? (float)$request->survey / 5.0 : 0.5);

        // Gabungkan semua atribut ke dalam satu vektor pengguna dengan precision tinggi
        // Urutan: [harga, rating, jarak, fasilitas..., survey]
        $userVector = array_merge([$harga, $rating, $jarak], $fasilitasVector, [$survey]);

        return $userVector;
    }

    // Fungsi untuk menghitung jarak antara dua titik koordinat (Haversine Formula)
    private function hitungJarak($lat2, $lon2)
    {
        $lat1 = -8.295814841001143; // Koordinat pusat (Poliwangi)
        $lon1 = 114.3076786627924;

        $earthRadius = 6371; // Radius bumi dalam kilometer

        $dLat = deg2rad($lat2 - $lat1); //menghitung selisih lintang bujur dan diubah ke radian
        $dLon = deg2rad($lon2 - $lon1);

        // Rumus haversine
        $a = sin($dLat / 2) * sin($dLat / 2) + //mencari selisih antar dua titik
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * //mempertimbangkan kelengkungan bumi secara horizontal.
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a)); //menghitung sudut tengah antar titik 
        return $earthRadius * $c; //Nilai yang dikembalikan sebagai jarak antar dua titik berdasarkan lokasi pengguna dan kos.
    }
}
