<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Http\Request;

class FixRecommendationDataCommand extends Command
{
    protected $signature = 'fix:recommendation-data {--user_id=1} {--force}';
    protected $description = 'Fix and synchronize recommendation data';

    public function handle()
    {
        $userId = $this->option('user_id');
        $forceMode = $this->option('force');
        
        $this->info("=== PERBAIKAN DATA REKOMENDASI ===");
        $this->info("User ID: {$userId}");
        
        if ($forceMode) {
            $this->info("\n=== STEP 1: PERBAIKAN NORMALISASI ===");
            $this->fixNormalizationData();
            
            $this->info("\n=== STEP 2: PERHITUNGAN ULANG REKOMENDASI ===");
            $this->recalculateRecommendations($userId);
            
            $this->info("\n=== STEP 3: VERIFIKASI ===");
            $this->verifyResults($userId);
        } else {
            $this->info("\nGunakan --force untuk memperbaiki otomatis");
            $this->showUsage();
        }
    }
    
    private function fixNormalizationData()
    {
        $this->info("Memperbaiki data normalisasi...");
        
        $allKos = Kos::all();
        $fixedCount = 0;
        
        foreach ($allKos as $kos) {
            // Calculate correct normalization
            $hargaNormalized = $this->calculateHargaNormalization($kos->harga);
            $ratingNormalized = $kos->nilai_rating / 5;
            $jarakNormalized = $this->calculateJarakNormalization($kos->latitude, $kos->longitude);
            
            // Get facilities
            $fasilitasIds = $kos->fasilitas()->pluck('fasilitas.id')->toArray();
            $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
            $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($fasilitasIds) {
                return in_array($fasilitas->id, $fasilitasIds) ? 1 : 0;
            })->toArray();
            
            // Update or create normalization
            NormalisasiKos::updateOrCreate(
                ['id_kos' => $kos->id],
                [
                    'harga_normalized' => $hargaNormalized,
                    'rating_normalized' => $ratingNormalized,
                    'jarak_normalized' => $jarakNormalized,
                    'fasilitas_normalized' => $fasilitasVector
                ]
            );
            
            $fixedCount++;
        }
        
        $this->info("✓ Berhasil memperbaiki {$fixedCount} kos");
    }
    
    private function calculateHargaNormalization($harga)
    {
        // Convert string to numeric value
        $numericHarga = (int) preg_replace('/[^0-9]/', '', $harga);
        
        if ($numericHarga < 500000) {
            return 1; // Harga < 500k = 1 (terbaik)
        } elseif ($numericHarga <= 1000000) {
            return 0.5; // Harga 500k-1M = 0.5 (sedang)
        } else {
            return 0; // Harga > 1M = 0 (terburuk)
        }
    }
    
    private function calculateJarakNormalization($latitude, $longitude)
    {
        // Calculate distance from center (you can adjust this)
        $centerLat = -8.3006295;
        $centerLng = 114.3043753;
        
        $distance = $this->calculateDistance($latitude, $longitude, $centerLat, $centerLng);
        
        if ($distance < 1) {
            return 1; // Jarak < 1 km = 1 (terbaik)
        } elseif ($distance <= 3) {
            return 0.5; // Jarak 1-3 km = 0.5 (sedang)
        } else {
            return 0; // Jarak > 3 km = 0 (terburuk)
        }
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344; // Convert to kilometers
    }
    
    private function recalculateRecommendations($userId)
    {
        $this->info("Menghitung ulang rekomendasi untuk user {$userId}...");
        
        // Default request for recalculation
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
            $similarity = $this->calculateSimilarity($kos->id, $request);
            
            // Update database
            HasilRekomendasi::updateOrCreate(
                [
                    'id_user' => $userId,
                    'id_kos' => $kos->id
                ],
                [
                    'nilai_similarity' => $similarity
                ]
            );
            
            $updatedCount++;
        }
        
        $this->info("✓ Berhasil mengupdate {$updatedCount} rekomendasi");
    }
    
    private function verifyResults($userId)
    {
        $this->info("Memverifikasi hasil...");
        
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
                $controllerSimilarity = $this->calculateSimilarity($kos->id, $request);
                
                // Round controller similarity to match database precision
                $roundedControllerSimilarity = round($controllerSimilarity, 3);
                $roundedDbSimilarity = round($dbSimilarity, 3);
                
                if (abs($roundedControllerSimilarity - $roundedDbSimilarity) < 0.001) {
                    $exactMatches++;
                }
            }
        }
        
        $this->info("Total Kos: {$totalKos}");
        $this->info("Exact Matches: {$exactMatches}");
        $this->info("Accuracy: " . round(($exactMatches / $totalKos) * 100, 2) . "%");
        
        if ($exactMatches == $totalKos) {
            $this->info("🎉 SEMUA DATA SUDAH 100% SINCHRON!");
        } else {
            $this->warn("⚠️  Masih ada perbedaan pada " . ($totalKos - $exactMatches) . " kos");
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
    
    private function showUsage()
    {
        $this->info("Gunakan --force untuk memperbaiki otomatis:");
        $this->info("  php artisan fix:recommendation-data --force");
        $this->info("  php artisan fix:recommendation-data --force --user_id=2");
    }
}
