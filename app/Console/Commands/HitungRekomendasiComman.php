<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\User;

class HitungRekomendasiComman extends Command
{
    protected $signature = 'rekomendasi:hitung
                            {--user_id= : ID user untuk menghitung rekomendasi}
                            {--harga= : Pilihan harga (< Rp. 500.000 / Rp. 500.000 - Rp. 1.000.000 / > Rp. 1.000.000)}
                            {--rating= : Rating minimal (1-5)}
                            {--jarak= : Jarak (< 1 km / 1 - 3 km / > 3 km)}
                            {--fasilitas=* : Array ID fasilitas yang dipilih}
                            {--survey= : Nilai survey (0-5)}';

    protected $description = 'Hitung rekomendasi kos berbasis cosine similarity untuk user tertentu';

    public function handle()
    {
        $userId = $this->option('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User dengan ID {$userId} tidak ditemukan!");
            return 1;
        }

        $this->info("Menghitung rekomendasi untuk user: {$user->name} (ID: {$user->id})");

        // 1. Bangun vektor user
        $userVector = $this->buildUserVector();

        // 2. Ambil semua data normalisasi kos
        $dataNormalisasi = NormalisasiKos::with('kos')->get();

        $similarities = [];

        foreach ($dataNormalisasi as $data) {
            $skorSurvey = SurveyKepuasan::where('id_kos', $data->id_kos)->avg('skor') ?? 2.5;
            $skorSurveyNormalized = $skorSurvey / 5;

            $fasilitasKos = $data->fasilitas_normalized ?? [];

            $kosVector = array_merge([
                $data->harga_normalized,
                $data->rating_normalized,
                $data->jarak_normalized,
            ], $fasilitasKos, [$skorSurveyNormalized]);

            $similarity = $this->cosineSimilarity($userVector, $kosVector);

            HasilRekomendasi::updateOrCreate(
                ['id_user' => $user->id, 'id_kos' => $data->id_kos],
                ['nilai_similarity' => $similarity]
            );

            $similarities[] = [
                'kos' => $data->kos->nama_kos,
                'similarity' => round($similarity, 3),
            ];
        }

        // Urutkan dari skor tertinggi
        usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        $this->info("Hasil rekomendasi:");
        foreach ($similarities as $i => $item) {
            $this->line(($i + 1) . ". {$item['kos']} → similarity: {$item['similarity']}");
        }

        $this->info("Selesai menghitung dan menyimpan rekomendasi.");
        return 0;
    }

    private function buildUserVector()
    {
        $hargaInput = $this->option('harga');
        $ratingInput = $this->option('rating');
        $jarakInput = $this->option('jarak');
        $fasilitasInput = $this->option('fasilitas') ?: [];
        $surveyInput = $this->option('survey');

        // Normalisasi harga
        $harga = match($hargaInput) {
            '< Rp. 500.000' => 1,
            'Rp. 500.000 - Rp. 1.000.000' => 0.5,
            '> Rp. 1.000.000' => 0,
            default => 0.5,
        };

        // Normalisasi rating
        $rating = $ratingInput ? $ratingInput / 5 : 0.5;

        // Normalisasi jarak
        $jarak = match($jarakInput) {
            '< 1 km' => 1,
            '1 - 3 km' => 0.5,
            '> 3 km' => 0,
            default => 0.5,
        };

        // Normalisasi fasilitas
        $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
        $fasilitasVector = $allFasilitas->map(fn($f) => in_array($f->id, $fasilitasInput) ? 1 : 0)->toArray();

        // Normalisasi survey
        $survey = $surveyInput ? $surveyInput / 5 : 0.5;

        return array_merge([$harga, $rating, $jarak], $fasilitasVector, [$survey]);
    }

    private function cosineSimilarity(array $vectorA, array $vectorB)
    {
        $length = min(count($vectorA), count($vectorB));
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += pow($vectorA[$i], 2);
            $magnitudeB += pow($vectorB[$i], 2);
        }

        if ($magnitudeA == 0 || $magnitudeB == 0) return 0;

        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }
}
