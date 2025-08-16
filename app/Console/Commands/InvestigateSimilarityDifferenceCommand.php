<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Http\Request;

class InvestigateSimilarityDifferenceCommand extends Command
{
    protected $signature = 'investigate:similarity-difference {--kos_id=} {--user_id=1}';
    protected $description = 'Investigate why there are differences between database and controller similarity values';

    public function handle()
    {
        $kosId = $this->option('kos_id');
        $userId = $this->option('user_id');
        
        if ($kosId) {
            $this->investigateSpecificKos($kosId, $userId);
        } else {
            $this->investigateAllKos($userId);
        }
    }
    
    private function investigateSpecificKos($kosId, $userId)
    {
        $this->info("=== INVESTIGASI PERBEDAAN SIMILARITY KOS ID {$kosId} ===");
        
        // Get database similarity
        $dbSimilarity = HasilRekomendasi::where('id_user', $userId)
            ->where('id_kos', $kosId)
            ->value('nilai_similarity');
            
        if (!$dbSimilarity) {
            $this->error("Data similarity tidak ditemukan di database!");
            return;
        }
        
        $this->info("Database Similarity: " . number_format($dbSimilarity, 12));
        
        // Calculate controller similarity
        $request = $this->getDefaultRequest();
        $controllerSimilarity = $this->calculateSimilarity($kosId, $request);
        
        $this->info("Controller Similarity: " . number_format($controllerSimilarity, 12));
        
        $difference = abs($dbSimilarity - $controllerSimilarity);
        $this->info("Selisih: " . number_format($difference, 12));
        
        if ($difference > 0.001) {
            $this->warn("⚠️  Ditemukan perbedaan signifikan!");
            $this->investigateRootCause($kosId, $request, $dbSimilarity, $controllerSimilarity);
        } else {
            $this->info("✓ Perbedaan minimal (dalam toleransi)");
        }
    }
    
    private function investigateAllKos($userId)
    {
        $this->info("=== INVESTIGASI SEMUA KOS ===");
        
        $allKos = Kos::all();
        $differences = [];
        
        $request = $this->getDefaultRequest();
        
        foreach ($allKos as $kos) {
            $dbSimilarity = HasilRekomendasi::where('id_user', $userId)
                ->where('id_kos', $kos->id)
                ->value('nilai_similarity');
                
            if ($dbSimilarity) {
                $controllerSimilarity = $this->calculateSimilarity($kos->id, $request);
                $difference = abs($dbSimilarity - $controllerSimilarity);
                
                if ($difference > 0.001) {
                    $differences[] = [
                        'id' => $kos->id,
                        'nama' => $kos->nama_kos,
                        'db' => $dbSimilarity,
                        'controller' => $controllerSimilarity,
                        'difference' => $difference
                    ];
                }
            }
        }
        
        if (empty($differences)) {
            $this->info("✓ Semua similarity sudah sesuai (dalam toleransi)");
        } else {
            $this->warn("⚠️  Ditemukan perbedaan pada " . count($differences) . " kos:");
            
            usort($differences, function($a, $b) {
                return $b['difference'] <=> $a['difference'];
            });
            
            foreach ($differences as $diff) {
                $this->info("  Kos ID {$diff['id']} ({$diff['nama']}):");
                $this->info("    DB: " . number_format($diff['db'], 12));
                $this->info("    Controller: " . number_format($diff['controller'], 12));
                $this->info("    Selisih: " . number_format($diff['difference'], 12));
                $this->info("");
            }
        }
    }
    
    private function investigateRootCause($kosId, Request $request, $dbSimilarity, $controllerSimilarity)
    {
        $this->info("\n=== INVESTIGASI PENYEBAB ===");
        
        // Get kos data
        $kos = Kos::find($kosId);
        $normalisasi = NormalisasiKos::where('id_kos', $kosId)->first();
        
        if (!$kos || !$normalisasi) {
            $this->error("Data kos atau normalisasi tidak ditemukan!");
            return;
        }
        
        // Build vectors
        $userVector = $this->buildUserVector($request);
        $kosVector = $this->buildKosVector($kosId);
        
        $this->info("User Vector: " . json_encode($userVector));
        $this->info("Kos Vector: " . json_encode($kosVector));
        
        // Check survey data
        $surveyData = SurveyKepuasan::where('id_kos', $kosId)->get();
        $this->info("\nSurvey Data:");
        if ($surveyData->count() > 0) {
            foreach ($surveyData as $survey) {
                $this->info("  User ID {$survey->id_user}: Skor {$survey->skor}");
            }
            $this->info("  Rata-rata: " . $surveyData->avg('skor'));
            $this->info("  Normalized: " . ($surveyData->avg('skor') / 5));
        } else {
            $this->info("  Tidak ada data survey");
        }
        
        // Check normalization data
        $this->info("\nNormalization Data:");
        $this->info("  Harga: {$kos->harga} → {$normalisasi->harga_normalized}");
        $this->info("  Rating: {$kos->nilai_rating} → {$normalisasi->rating_normalized}");
        $this->info("  Jarak: {$normalisasi->jarak_normalized}");
        $this->info("  Fasilitas: " . json_encode($normalisasi->fasilitas_normalized));
        
        // Check if data was updated recently
        $this->info("\nTimestamps:");
        $this->info("  Kos updated: " . $kos->updated_at);
        $this->info("  Normalisasi updated: " . $normalisasi->updated_at);
        
        // Recalculate step by step
        $this->info("\n=== RECALCULATION STEP BY STEP ===");
        $this->recalculateStepByStep($userVector, $kosVector);
    }
    
    private function recalculateStepByStep($userVector, $kosVector)
    {
        $length = min(count($userVector), count($kosVector));
        
        $this->info("Vector Length: {$length}");
        
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;
        
        for ($i = 0; $i < $length; $i++) {
            $product = $userVector[$i] * $kosVector[$i];
            $dotProduct += $product;
            $magnitudeA += pow($userVector[$i], 2);
            $magnitudeB += pow($kosVector[$i], 2);
            
            $this->info("  Index {$i}: User[{$i}]={$userVector[$i]}, Kos[{$i}]={$kosVector[$i]}, Product={$product}");
        }
        
        $this->info("\nCalculation:");
        $this->info("  Dot Product: {$dotProduct}");
        $this->info("  Magnitude A: {$magnitudeA}");
        $this->info("  Magnitude B: {$magnitudeB}");
        
        if ($magnitudeA == 0 || $magnitudeB == 0) {
            $this->info("  Similarity: 0 (zero magnitude)");
        } else {
            $sqrtA = sqrt($magnitudeA);
            $sqrtB = sqrt($magnitudeB);
            $denominator = $sqrtA * $sqrtB;
            $similarity = $dotProduct / $denominator;
            
            $this->info("  √Magnitude A: {$sqrtA}");
            $this->info("  √Magnitude B: {$sqrtB}");
            $this->info("  Denominator: {$denominator}");
            $this->info("  Similarity: {$dotProduct} / {$denominator} = {$similarity}");
        }
    }
    
    private function getDefaultRequest()
    {
        $request = new Request();
        $request->merge([
            'harga' => '< Rp. 500.000',
            'rating' => 5,
            'jarak' => '< 1 km',
            'fasilitas' => [17, 19],
            'survey' => 4,
            'metode' => 'rekomendasi'
        ]);
        return $request;
    }
    
    private function buildUserVector(Request $request)
    {
        // Normalisasi harga berdasarkan pilihan pengguna
        // Semakin kecil harga, semakin besar nilai (1 = terbaik)
        $harga = match($request->harga) {
            '< Rp. 500.000' => 1,    // Harga < 500k = 1 (terbaik)
            'Rp. 500.000 - Rp. 1.000.000' => 0.5,  // Harga 500k-1M = 0.5 (sedang)
            '> Rp. 1.000.000' => 0,  // Harga > 1M = 0 (terburuk)
            default => 0.5
        };

        // Normalisasi rating jika tersedia
        $rating = ($request->filled('rating') ? $request->rating / 5 : 0.5);

        // Normalisasi jarak berdasarkan input
        // Semakin kecil jarak, semakin besar nilai (1 = terbaik)
        $jarak = match($request->jarak) {
            '< 1 km' => 1,      // Jarak < 1 km = 1 (terbaik)
            '1 - 3 km' => 0.5,  // Jarak 1-3 km = 0.5 (sedang)
            '> 3 km' => 0,      // Jarak > 3 km = 0 (terburuk)
            default => 0.5
        };

        // Ambil semua fasilitas dan bangun vektor fasilitas berdasarkan input
        // Gunakan ID untuk konsistensi dengan database
        $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
        $userFasilitas = $request->filled('fasilitas') ? $request->fasilitas : [];

        $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($userFasilitas) {
            return in_array($fasilitas->id, $userFasilitas) ? 1 : 0;
        })->toArray();

        // Normalisasi skor survey pengguna
        $survey = ($request->filled('survey') ? $request->survey / 5 : 0.5);

        // Gabungkan semua atribut ke dalam satu vektor pengguna
        // Urutan: [harga, rating, jarak, fasilitas..., survey]
        $userVector = array_merge([$harga, $rating, $jarak], $fasilitasVector, [$survey]);

        return $userVector;
    }
    
    private function buildKosVector($kosId)
    {
        $normalisasi = NormalisasiKos::where('id_kos', $kosId)->first();
        if (!$normalisasi) {
            return [];
        }
        
        $skorSurvey = SurveyKepuasan::where('id_kos', $kosId)->avg('skor') ?? 2.5;
        $skorSurveyNormalized = $skorSurvey / 5;
        
        $fasilitasKos = $normalisasi->fasilitas_normalized ?? [];
        $kosVector = array_merge([
            $normalisasi->harga_normalized,
            $normalisasi->rating_normalized,
            $normalisasi->jarak_normalized,
        ], $fasilitasKos, [$skorSurveyNormalized]);
        
        return $kosVector;
    }
    
    private function calculateSimilarity($kosId, Request $request)
    {
        // Build user vector
        $userVector = $this->buildUserVector($request);
        
        // Get kos data (always fresh from database)
        $normalisasi = NormalisasiKos::where('id_kos', $kosId)->first();
        if (!$normalisasi) {
            return 0;
        }
        
        // Build kos vector
        $skorSurvey = SurveyKepuasan::where('id_kos', $kosId)->avg('skor') ?? 2.5;
        $skorSurveyNormalized = $skorSurvey / 5;
        
        $fasilitasKos = $normalisasi->fasilitas_normalized ?? [];
        $kosVector = array_merge([
            $normalisasi->harga_normalized,
            $normalisasi->rating_normalized,
            $normalisasi->jarak_normalized,
        ], $fasilitasKos, [$skorSurveyNormalized]);
        
        // Calculate similarity
        return $this->cosineSimilarity($userVector, $kosVector);
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

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }
}
