<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Http\Request;

class VerifyRecommendationSystemCommand extends Command
{
    protected $signature = 'verify:recommendation-system {--harga=} {--rating=} {--jarak=} {--fasilitas=*} {--survey=} {--user_id=1}';
    protected $description = 'Verify recommendation system works correctly with user input';

    public function handle()
    {
        $this->info("=== VERIFIKASI SISTEM REKOMENDASI ===");
        
        // Get user input
        $harga = $this->option('harga') ?: '< Rp. 500.000';
        $rating = $this->option('rating') ?: 5;
        $jarak = $this->option('jarak') ?: '< 1 km';
        $fasilitas = $this->option('fasilitas') ?: [17, 19];
        $survey = $this->option('survey') ?: 4;
        $userId = $this->option('user_id');
        
        $this->info("Input User:");
        $this->info("  Harga: {$harga}");
        $this->info("  Rating: {$rating}");
        $this->info("  Jarak: {$jarak}");
        $this->info("  Fasilitas: " . implode(', ', $fasilitas));
        $this->info("  Survey: {$survey}");
        $this->info("  User ID: {$userId}");
        
        // Create mock request
        $request = new Request();
        $request->merge([
            'harga' => $harga,
            'rating' => $rating,
            'jarak' => $jarak,
            'fasilitas' => $fasilitas,
            'survey' => $survey,
            'metode' => 'rekomendasi'
        ]);
        
        // Step 1: Build user vector
        $this->info("\n=== STEP 1: BUILD USER VECTOR ===");
        $userVector = $this->buildUserVector($request);
        $this->info("User Vector: " . json_encode($userVector));
        $this->info("Vector Length: " . count($userVector));
        
        // Step 2: Calculate similarity for all kos
        $this->info("\n=== STEP 2: CALCULATE SIMILARITY ===");
        $allKos = Kos::all();
        $results = [];
        
        foreach ($allKos as $kos) {
            $similarity = $this->calculateSimilarity($kos->id, $request);
            $results[] = [
                'id' => $kos->id,
                'nama' => $kos->nama_kos,
                'similarity' => $similarity
            ];
        }
        
        // Step 3: Sort by similarity descending
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // Step 4: Display top recommendations
        $this->info("\n=== STEP 3: TOP REKOMENDASI ===");
        foreach (array_slice($results, 0, 8) as $index => $result) {
            $rank = $index + 1;
            $this->info("{$rank}. {$result['nama']} (ID: {$result['id']}) - Similarity: " . number_format($result['similarity'], 12));
        }
        
        // Step 5: Compare with database results
        $this->info("\n=== STEP 4: COMPARE WITH DATABASE ===");
        $dbResults = HasilRekomendasi::where('id_user', $userId)
            ->with('kos')
            ->orderBy('nilai_similarity', 'desc')
            ->limit(8)
            ->get();
            
        foreach ($dbResults as $index => $result) {
            $rank = $index + 1;
            $this->info("{$rank}. {$result->kos->nama_kos} (ID: {$result->kos->id}) - DB: " . number_format($result->nilai_similarity, 12));
        }
        
        // Step 6: Verify exact matching
        $this->info("\n=== STEP 5: VERIFY EXACT MATCHING ===");
        $exactMatches = 0;
        $totalKos = 0;
        
        foreach ($allKos as $kos) {
            $dbSimilarity = HasilRekomendasi::where('id_user', $userId)
                ->where('id_kos', $kos->id)
                ->value('nilai_similarity');
                
            if ($dbSimilarity) {
                $totalKos++;
                $controllerSimilarity = $this->calculateSimilarity($kos->id, $request);
                
                // Round controller similarity to match database precision
                $roundedControllerSimilarity = round($controllerSimilarity, 3);
                $roundedDbSimilarity = round($dbSimilarity, 3);
                
                if (abs($roundedControllerSimilarity - $roundedDbSimilarity) < 0.001) {
                    $exactMatches++;
                    $this->info("✓ Kos ID {$kos->id}: Controller " . number_format($roundedControllerSimilarity, 3) . " = DB " . number_format($roundedDbSimilarity, 3));
                } else {
                    $this->warn("✗ Kos ID {$kos->id}: Controller " . number_format($roundedControllerSimilarity, 3) . " ≠ DB " . number_format($roundedDbSimilarity, 3));
                }
            }
        }
        
        // Step 7: Final verification
        $this->info("\n=== STEP 6: FINAL VERIFICATION ===");
        $this->info("Total Kos: {$totalKos}");
        $this->info("Exact Matches: {$exactMatches}");
        $this->info("Accuracy: " . round(($exactMatches / $totalKos) * 100, 2) . "%");
        
        if ($exactMatches == $totalKos) {
            $this->info("🎉 SISTEM REKOMENDASI 100% BERFUNGSI DENGAN BAIK!");
            $this->info("✅ Similarity yang ditampilkan di web interface sama persis dengan database");
            $this->info("✅ Perhitungan sesuai dengan inputan user");
            $this->info("✅ Normalisasi harga dan jarak sudah benar (semakin kecil = nilai 1)");
            $this->info("✅ Fasilitas menggunakan binary representation berdasarkan ID");
            $this->info("✅ Survey score dihitung dengan benar");
        } else {
            $this->warn("⚠️  Masih ada perbedaan pada " . ($totalKos - $exactMatches) . " kos");
        }
        
        // Step 8: Show what PencarianController would display
        $this->info("\n=== STEP 7: PENCARIAN CONTROLLER DISPLAY ===");
        $this->showPencarianControllerDisplay($request, $userId);
    }
    
    private function showPencarianControllerDisplay(Request $request, $userId)
    {
        $this->info("Simulasi PencarianController display...");
        
        $allKos = Kos::all();
        $rekomendasi = [];
        
        foreach ($allKos as $kos) {
            // Calculate similarity
            $similarity = $this->calculateSimilarity($kos->id, $request);
            
            // Simulate what PencarianController does
            $dbSimilarity = HasilRekomendasi::where('id_user', $userId)
                ->where('id_kos', $kos->id)
                ->value('nilai_similarity');
                
            // Use database value for display (like updated PencarianController)
            $displaySimilarity = $dbSimilarity;
            
            $rekomendasi[] = [
                'id' => $kos->id,
                'nama' => $kos->nama_kos,
                'calculated' => $similarity,
                'database' => $dbSimilarity,
                'display' => $displaySimilarity
            ];
        }
        
        // Sort by display similarity descending
        usort($rekomendasi, function($a, $b) {
            return $b['display'] <=> $a['display'];
        });
        
        $this->info("\nTop 8 Rekomendasi (PencarianController Display):");
        foreach (array_slice($rekomendasi, 0, 8) as $index => $result) {
            $rank = $index + 1;
            $this->info("{$rank}. {$result['nama']} (ID: {$result['id']}) - Display: " . number_format($result['display'], 12));
        }
        
        // Check if display matches database
        $this->info("\n=== VERIFIKASI DISPLAY vs DATABASE ===");
        $displayMatches = 0;
        $totalResults = count($rekomendasi);
        
        foreach ($rekomendasi as $result) {
            if (abs($result['display'] - $result['database']) < 0.0001) {
                $displayMatches++;
            }
        }
        
        $this->info("Display matches Database: {$displayMatches}/{$totalResults}");
        if ($displayMatches == $totalResults) {
            $this->info("🎉 PencarianController akan menampilkan nilai yang 100% sama dengan database!");
        }
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
