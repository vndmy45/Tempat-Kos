<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
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
        $fasilitas = Fasilitas::all();

        // Ambil semua rating unik dari tabel kos
        $ratings = Kos::selectRaw('FLOOR(nilai_rating) as rating')
            ->whereNotNull('nilai_rating')
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->pluck('rating');

        // Cek apakah user memilih metode rekomendasi
        if ($request->metode === 'rekomendasi') {
        $kos_list = $this->prosesRekomendasi($request);
        return view('pencarian', [
            'kos_list' => $kos_list,
            'fasilitas' => $fasilitas,
            'ratings' => $ratings,
        ]);
    }
     // Jika pakai metode filter manual
        $latitudeRef = -8.295807953836674;
        $longitudeRef = 114.30768351352297;

        // Query dengan kalkulasi jarak menggunakan haversine formula
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
            ->with(['fasilitas', 'gambarKos']) // eager load relasi
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
                $query->where('nilai_rating', '>=', $request->rating);
            })
            ->when($request->jarak, function ($query) use ($request) {
                if ($request->jarak === '< 1 km') {
                    $query->havingRaw('jarak < ?', [1]);
                } elseif ($request->jarak === '1 - 3 km') {
                    $query->havingRaw('jarak BETWEEN ? AND ?', [1, 3]);
                } elseif ($request->jarak === '> 3 km') {
                    $query->havingRaw('jarak > ?', [3]);
                }
            })
            ->when($request->has('fasilitas') && is_array($request->fasilitas), function ($query) use ($request) {
                $query->whereHas('fasilitas', function ($subQuery) use ($request) {
                    $subQuery->whereIn('fasilitas.id', $request->fasilitas);
                });
            })

            ->orderBy('jarak') // urutkan berdasarkan jarak terdekat
            ->paginate(9)
            ->appends($request->all());

        return view('pencarian', [
            'kos_list' => $kos,
            'fasilitas' => $fasilitas,
            'ratings' => $ratings,
        ]);
    }

     private function prosesRekomendasi(Request $request)
    {
        $user = auth()->user();
        $userVector = $this->buildUserVector($request);

        $weightHarga = 0.3;
        $weightJarak = 0.3;
        $weightRating = 0.4;
        $weightFasilitas = 0.1;
        $weightSurvey = 0.4;

        $dataNormalisasi = NormalisasiKos::with('kos.gambarKos')->get();
        $rekomendasi = [];

        foreach ($dataNormalisasi as $data) {
        $skorSurvey = SurveyKepuasan::where('id_kos', $data->id_kos)->avg('skor') ?? 3;
        $skorSurveyNormalized = $skorSurvey / 5;
        $kosVector = array_merge([
            $data->harga_normalized * $weightHarga,
            $data->jarak_normalized * $weightJarak,
            $data->rating_normalized * $weightRating,
        ], array_map(function ($fasilitasValue) use ($weightFasilitas) {
            return $fasilitasValue * $weightFasilitas;
        }, $data->fasilitas_normalized));

        $kosVector[] = $skorSurveyNormalized * $weightSurvey;

        $similarity = $this->cosineSimilarity($userVector, $kosVector);

            HasilRekomendasi::updateOrCreate([
                'id_user' => $user->id,
                'id_kos' => $data->id_kos,
            ], [
                'nilai_similarity' => $similarity
            ]);

            $data->kos->similarity = $similarity;
            $data->kos->jarak = $this->hitungJarak($data->kos->latitude, $data->kos->longitude);
            $rekomendasi[] = $data->kos;
        }

        $topN = 8;

        return $this->paginateCollection(
            collect($rekomendasi)
                ->sortByDesc('similarity')
                ->take($topN) // ambil 8 besar dulu
                ->values()
        );
    }

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
    private function cosineSimilarity(array $vectorA, array $vectorB)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += pow($vectorA[$i], 2);
            $magnitudeB += pow($vectorB[$i], 2);
        }

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0; // Hindari pembagian dengan nol
        }

        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }

    private function buildUserVector(Request $request)
    {
        // Bobot masing-masing fitur
        $weightHarga = 0.3;
        $weightJarak = 0.3;
        $weightRating = 0.4;
        $weightFasilitas = 0.1;// nanti dikalikan per elemen
        $weightSurvey = 0.4; 

        $harga = match($request->harga) {
            '< Rp. 500.000' => 0,
            'Rp. 500.000 - Rp. 1.000.000' => 0.5,
            '> Rp. 1.000.000' => 1,
            default => 0.5
        } * $weightHarga;

        $rating = ($request->filled('rating') ? $request->rating / 5 : 0.5) * $weightRating;

        $jarak = match($request->jarak) {
            '< 1 km' => 0,
            '1 - 3 km' => 0.5,
            '> 3 km' => 1,
            default => 0.5
        } * $weightJarak;

        $allFasilitas = Fasilitas::orderBy('index')->get(['id', 'index']);
        $maxIndex = $allFasilitas->max('index');
        $userFasilitas = $request->filled('fasilitas') ? $request->fasilitas : [];

        $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($userFasilitas, $maxIndex, $weightFasilitas) {
            return in_array($fasilitas->id, $userFasilitas)
                ? ($fasilitas->index / $maxIndex) * $weightFasilitas
                : 0;
        })->toArray();
        
        $survey = ($request->filled('survey') ? $request->survey / 5 : 0.5) * $weightSurvey;
        
        $userVector = array_merge([$harga, $jarak, $rating], $fasilitasVector, [$survey]);

        return $userVector;
    }

    private function hitungJarak($lat2, $lon2)
    {
        $lat1 = -8.295814841001143; //koordinate preferensi
        $lon1 = 114.3076786627924;

        $earthRadius = 6371; // Radius bumi dalam km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
