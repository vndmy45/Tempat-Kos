<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Http\Request;

class SyncExactRecommendationCommand extends Command
{
    protected $signature = 'sync:exact-recommendation {--user_id=1} {--force}';
    protected $description = 'Sync exact recommendation values with precise calculations';

    public function handle()
    {
        $userId = $this->option('user_id');
        $forceMode = $this->option('force');
        
        $this->info("=== SINCHRONISASI EXACT RECOMMENDATION ===");
        $this->info("User ID: {$userId}");
        
        if ($forceMode) {
            $this->info("\n=== STEP 1: PERHITUNGAN EXACT ===");
            $this->calculateExactRecommendations($userId);
            
            $this->info("\n=== STEP 2: VERIFIKASI EXACT ===");
            $this->verifyExactResults($userId);
        } else {
            $this->info("\nGunakan --force untuk melakukan synchronisasi exact");
            $this->showUsage();
        }
    }
    
    private function calculateExactRecommendations($userId)
    {
        $this->info("Menghitung rekomendasi dengan precision exact...");
        
        // Default request for exact calculation
        $request = new Request();
        $request->merge([
            'harga' => '< Rp. 500.000',
            'rating' => 5,
            'jarak' => '< 1 km',
            'fasilitas' => [17, 19],
            'survey' => 4,
            'metode' => 'rekomendasi'
        ]);
        
        $allKos = Kos::all();
        $updatedCount = 0;
        
        foreach ($allKos as $kos) {
            $exactSimilarity = $this->calculateExactSimilarity($kos->id, $request);
            
            // Update database with exact value
            HasilRekomendasi::updateOrCreate(
                [
                    'id_user' => $userId,
                    'id_kos' => $kos->id
                ],
                [
                    'nilai_similarity' => $exactSimilarity
                ]
            );
            
            $updatedCount++;
        }
        
        $this->info("✓ Berhasil mengupdate {$updatedCount} rekomendasi dengan precision exact");
    }
    
    private function verifyExactResults($userId)
    {
        $this->info("Memverifikasi hasil exact...");
        
        $request = new Request();
        $request->merge([
            'harga' => '< Rp. 500.000',
            'rating' => 5,
            'jarak' => '< 1 km',
            'fasilitas' => [17, 19],
            'survey' => 4,
            'metode' => 'rekomendasi'
        ]);
        
        $allKos = Kos::all();
        $exactMatches = 0;
        $totalKos = 0;
        
        foreach ($allKos as $kos) {
            $dbSimilarity = HasilRekomendasi::where('id_user', $userId)
                ->where('id_kos', $kos->id)
                ->value('nilai_similarity');
                
            if ($dbSimilarity) {
                $totalKos++;
                $calculatedSimilarity = $this->calculateExactSimilarity($kos->id, $request);
                
                // Check if they match exactly (within float precision)
                if (abs($dbSimilarity - $calculatedSimilarity) < 0.000001) {
                    $exactMatches++;
                    $this->info("✓ Kos ID {$kos->id}: DB " . number_format($dbSimilarity, 12) . " = Calculated " . number_format($calculatedSimilarity, 12));
                } else {
                    $this->warn("✗ Kos ID {$kos->id}: DB " . number_format($dbSimilarity, 12) . " ≠ Calculated " . number_format($calculatedSimilarity, 12));
                }
            }
        }
        
        $this->info("\n=== VERIFIKASI EXACT ===");
        $this->info("Total Kos: {$totalKos}");
        $this->info("Exact Matches: {$exactMatches}");
        $this->info("Accuracy: " . round(($exactMatches / $totalKos) * 100, 2) . "%");
        
        if ($exactMatches == $totalKos) {
            $this->info("🎉 SEMUA REKOMENDASI SUDAH 100% EXACT!");
        } else {
            $this->warn("⚠️  Masih ada perbedaan pada " . ($totalKos - $exactMatches) . " kos");
        }
    }
    
    private function calculateExactSimilarity($kosId, Request $request)
    {
        // Build user vector with exact precision
        $userVector = $this->buildExactUserVector($request);
        
        // Get kos data with exact precision
        $normalisasi = NormalisasiKos::where('id_kos', $kosId)->first();
        if (!$normalisasi) {
            return 0;
        }
        
        // Build kos vector with exact precision
        $skorSurvey = SurveyKepuasan::where('id_kos', $kosId)->avg('skor') ?? 2.5;
        $skorSurveyNormalized = $skorSurvey / 5;
        
        $fasilitasKos = $normalisasi->fasilitas_normalized ?? [];
        $kosVector = array_merge([
            $normalisasi->harga_normalized,
            $normalisasi->rating_normalized,
            $normalisasi->jarak_normalized,
        ], $fasilitasKos, [$skorSurveyNormalized]);
        
        // Calculate similarity with maximum precision
        return $this->cosineSimilarityExact($userVector, $kosVector);
    }
    
    private function buildExactUserVector(Request $request)
    {
        // Normalisasi harga berdasarkan pilihan pengguna dengan precision exact
        $harga = match($request->harga) {
            '< Rp. 500.000' => 1.0,    // Harga < 500k = 1 (terbaik)
            'Rp. 500.000 - Rp. 1.000.000' => 0.5,  // Harga 500k-1M = 0.5 (sedang)
            '> Rp. 1.000.000' => 0.0,  // Harga > 1M = 0 (terburuk)
            default => 0.5
        };

        // Normalisasi rating dengan precision exact
        $rating = ($request->filled('rating') ? (float)$request->rating / 5.0 : 0.5);

        // Normalisasi jarak dengan precision exact
        $jarak = match($request->jarak) {
            '< 1 km' => 1.0,      // Jarak < 1 km = 1 (terbaik)
            '1 - 3 km' => 0.5,  // Jarak 1-3 km = 0.5 (sedang)
            '> 3 km' => 0.0,      // Jarak > 3 km = 0 (terburuk)
            default => 0.5
        };

        // Ambil semua fasilitas dan bangun vektor fasilitas dengan precision exact
        $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
        $userFasilitas = $request->filled('fasilitas') ? $request->fasilitas : [];

        $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($userFasilitas) {
            return in_array($fasilitas->id, $userFasilitas) ? 1.0 : 0.0;
        })->toArray();

        // Normalisasi skor survey dengan precision exact
        $survey = ($request->filled('survey') ? (float)$request->survey / 5.0 : 0.5);

        // Gabungkan semua atribut ke dalam satu vektor pengguna dengan precision exact
        $userVector = array_merge([$harga, $rating, $jarak], $fasilitasVector, [$survey]);

        return $userVector;
    }
    
    private function cosineSimilarityExact(array $vectorA, array $vectorB)
    {
        $length = min(count($vectorA), count($vectorB));
        
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
    
    private function showUsage()
    {
        $this->info("Gunakan --force untuk melakukan synchronisasi exact:");
        $this->info("  php artisan sync:exact-recommendation --force");
        $this->info("  php artisan sync:exact-recommendation --force --user_id=2");
    }
}
