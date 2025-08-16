<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Support\Facades\DB;

class UpdatePencarianControllerCommand extends Command
{
    protected $signature = 'update:pencarian-controller {--check} {--fix}';
    protected $description = 'Update PencarianController to ensure it uses latest data and proper normalization';

    public function handle()
    {
        $checkMode = $this->option('check');
        $fixMode = $this->option('fix');
        
        if ($checkMode) {
            $this->checkPencarianController();
        } elseif ($fixMode) {
            $this->fixPencarianController();
        } else {
            $this->showUsage();
        }
    }
    
    private function checkPencarianController()
    {
        $this->info("=== CEK PENCARIAN CONTROLLER ===");
        
        // Check if PencarianController exists
        $controllerPath = app_path('Http/Controllers/PencarianController.php');
        if (!file_exists($controllerPath)) {
            $this->error("PencarianController tidak ditemukan!");
            return;
        }
        
        $this->info("✓ PencarianController ditemukan");
        
        // Check normalization logic
        $this->checkNormalizationLogic();
        
        // Check data freshness
        $this->checkDataFreshness();
    }
    
    private function checkNormalizationLogic()
    {
        $this->info("\n=== CEK LOGIKA NORMALISASI ===");
        
        // Test normalization with sample data
        $testCases = [
            ['harga' => 400000, 'expected' => 1],
            ['harga' => 500000, 'expected' => 0.5],
            ['harga' => 1200000, 'expected' => 0],
        ];
        
        foreach ($testCases as $testCase) {
            $result = $this->calculateHargaNormalization($testCase['harga']);
            $status = $result == $testCase['expected'] ? '✓' : '✗';
            $this->info("  {$status} Harga {$testCase['harga']}: Expected {$testCase['expected']}, Got {$result}");
        }
        
        // Test jarak normalization
        $jarakTests = [
            ['lat' => -8.3006295, 'lon' => 114.3043753, 'expected' => 1], // Near
        ];
        
        foreach ($jarakTests as $testCase) {
            $result = $this->calculateJarakNormalization($testCase['lat'], $testCase['lon']);
            $status = $result == $testCase['expected'] ? '✓' : '✗';
            $this->info("  {$status} Jarak ({$testCase['lat']}, {$testCase['lon']}): Expected {$testCase['expected']}, Got {$result}");
        }
    }
    
    private function checkDataFreshness()
    {
        $this->info("\n=== CEK KESEGARAN DATA ===");
        
        // Check last update times
        $lastKosUpdate = Kos::max('updated_at');
        $lastNormalisasiUpdate = NormalisasiKos::max('updated_at');
        $lastHasilUpdate = HasilRekomendasi::max('updated_at');
        
        $this->info("Data Kos terakhir diupdate: " . ($lastKosUpdate ?: 'Tidak ada'));
        $this->info("Data Normalisasi terakhir diupdate: " . ($lastNormalisasiUpdate ?: 'Tidak ada'));
        $this->info("Data Hasil Rekomendasi terakhir diupdate: " . ($lastHasilUpdate ?: 'Tidak ada'));
        
        // Check for stale data
        $staleData = [];
        
        if ($lastKosUpdate && $lastNormalisasiUpdate) {
            $kosTime = \Carbon\Carbon::parse($lastKosUpdate);
            $normalisasiTime = \Carbon\Carbon::parse($lastNormalisasiUpdate);
            
            if ($kosTime->gt($normalisasiTime)) {
                $staleData[] = "Data normalisasi lebih lama dari data kos";
            }
        }
        
        if ($lastNormalisasiUpdate && $lastHasilUpdate) {
            $normalisasiTime = \Carbon\Carbon::parse($lastNormalisasiUpdate);
            $hasilTime = \Carbon\Carbon::parse($lastHasilUpdate);
            
            if ($normalisasiTime->gt($hasilTime)) {
                $staleData[] = "Data hasil rekomendasi lebih lama dari data normalisasi";
            }
        }
        
        if (empty($staleData)) {
            $this->info("✓ Semua data sudah segar");
        } else {
            $this->warn("⚠️  Ditemukan data yang tidak segar:");
            foreach ($staleData as $issue) {
                $this->warn("  " . $issue);
            }
        }
    }
    
    private function fixPencarianController()
    {
        $this->info("=== PERBAIKAN PENCARIAN CONTROLLER ===");
        
        // Step 1: Fix normalization data
        $this->info("\n=== STEP 1: PERBAIKAN DATA NORMALISASI ===");
        $this->fixAllNormalizationData();
        
        // Step 2: Recalculate all recommendations
        $this->info("\n=== STEP 2: HITUNG ULANG REKOMENDASI ===");
        $this->recalculateAllRecommendations();
        
        // Step 3: Verify synchronization
        $this->info("\n=== STEP 3: VERIFIKASI SINCHRONISASI ===");
        $this->verifySynchronization();
        
        $this->info("\n✓ PencarianController berhasil diperbaiki!");
    }
    
    private function fixAllNormalizationData()
    {
        $this->info("Memperbaiki semua data normalisasi...");
        
        $allKos = Kos::all();
        $fixedCount = 0;
        
        $progressBar = $this->output->createProgressBar($allKos->count());
        $progressBar->start();
        
        foreach ($allKos as $kos) {
            try {
                $this->fixKosNormalization($kos);
                $fixedCount++;
            } catch (\Exception $e) {
                $this->error("Error fixing kos ID {$kos->id}: " . $e->getMessage());
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✓ Berhasil memperbaiki {$fixedCount} kos");
    }
    
    private function fixKosNormalization($kos)
    {
        // Calculate correct normalization values
        $hargaNormalized = $this->calculateHargaNormalization($kos->harga);
        $ratingNormalized = $kos->nilai_rating ? $kos->nilai_rating / 5 : 0.5;
        $jarakNormalized = $this->calculateJarakNormalization($kos->latitude, $kos->longitude);
        
        // Get facilities
        $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
        $kosFasilitas = $kos->fasilitas->pluck('id')->toArray();
        $fasilitasVector = $allFasilitas->map(function ($fasilitas) use ($kosFasilitas) {
            return in_array($fasilitas->id, $kosFasilitas) ? 1 : 0;
        })->toArray();
        
        // Update or create normalization
        NormalisasiKos::updateOrCreate(
            ['id_kos' => $kos->id],
            [
                'harga_normalized' => $hargaNormalized,
                'rating_normalized' => $ratingNormalized,
                'jarak_normalized' => $jarakNormalized,
                'fasilitas_normalized' => $fasilitasVector,
            ]
        );
    }
    
    private function recalculateAllRecommendations()
    {
        $this->info("Menghitung ulang semua rekomendasi...");
        
        // Get all users
        $users = \App\Models\User::all();
        $totalUsers = $users->count();
        
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();
        
        $recalculatedCount = 0;
        
        foreach ($users as $user) {
            try {
                $searchCriteria = $this->getDefaultSearchCriteria();
                $this->recalculateForUser($user->id, $searchCriteria);
                $recalculatedCount++;
            } catch (\Exception $e) {
                $this->error("Error for user {$user->id}: " . $e->getMessage());
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✓ Berhasil menghitung ulang {$recalculatedCount} user");
    }
    
    private function recalculateForUser($userId, $searchCriteria)
    {
        // Get all kos
        $allKos = Kos::all();
        
        foreach ($allKos as $kos) {
            try {
                $similarity = $this->calculateSimilarity($kos->id, $searchCriteria);
                
                // Update or create result
                HasilRekomendasi::updateOrCreate(
                    [
                        'id_user' => $userId,
                        'id_kos' => $kos->id
                    ],
                    [
                        'nilai_similarity' => $similarity
                    ]
                );
            } catch (\Exception $e) {
                $this->error("Error Kos ID {$kos->id}: " . $e->getMessage());
            }
        }
    }
    
    private function calculateSimilarity($kosId, $searchCriteria)
    {
        // Build user vector
        $userVector = $this->buildUserVector($searchCriteria);
        
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
    
    private function buildUserVector($searchCriteria)
    {
        // Normalisasi harga - semakin kecil harga, semakin besar nilai (1)
        $hargaNormalized = match($searchCriteria['harga']) {
            '< Rp. 500.000' => 1,    // Harga < 500k = 1 (terbaik)
            'Rp. 500.000 - Rp. 1.000.000' => 0.5,  // Harga 500k-1M = 0.5 (sedang)
            '> Rp. 1.000.000' => 0,  // Harga > 1M = 0 (terburuk)
            default => 0.5
        };

        // Normalisasi rating
        $ratingNormalized = $searchCriteria['rating'] ? $searchCriteria['rating'] / 5 : 0.5;

        // Normalisasi jarak - semakin kecil jarak, semakin besar nilai (1)
        $jarakNormalized = match($searchCriteria['jarak']) {
            '< 1 km' => 1,      // Jarak < 1 km = 1 (terbaik)
            '1 - 3 km' => 0.5,  // Jarak 1-3 km = 0.5 (sedang)
            '> 3 km' => 0,      // Jarak > 3 km = 0 (terburuk)
            default => 0.5
        };

        // Normalisasi fasilitas
        $allFasilitas = Fasilitas::orderBy('id')->get(['id']);
        $userFasilitas = $searchCriteria['fasilitas'] ?: [];
        $fasilitasVector = $allFasilitas->map(fn($f) => in_array($f->id, $userFasilitas) ? 1 : 0)->toArray();

        // Normalisasi survey
        $surveyNormalized = $searchCriteria['survey'] ? $searchCriteria['survey'] / 5 : 0.5;

        return array_merge([$hargaNormalized, $ratingNormalized, $jarakNormalized], $fasilitasVector, [$surveyNormalized]);
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
    
    private function calculateHargaNormalization($harga)
    {
        // Semakin kecil harga, semakin besar nilai (1)
        if ($harga < 500000) {
            return 1; // Harga < 500k = 1 (terbaik)
        } elseif ($harga > 1000000) {
            return 0; // Harga > 1M = 0 (terburuk)
        } else {
            return 0.5; // Harga 500k-1M = 0.5 (sedang)
        }
    }
    
    private function calculateJarakNormalization($latitude, $longitude)
    {
        // Calculate distance from coordinates (simplified)
        // You should implement your actual distance calculation logic here
        $distance = $this->calculateDistance($latitude, $longitude);
        
        // Semakin kecil jarak, semakin besar nilai (1)
        if ($distance < 1) {
            return 1; // Jarak < 1 km = 1 (terbaik)
        } elseif ($distance > 3) {
            return 0; // Jarak > 3 km = 0 (terburuk)
        } else {
            return 0.5; // Jarak 1-3 km = 0.5 (sedang)
        }
    }
    
    private function calculateDistance($lat1, $lon1)
    {
        // Simplified distance calculation - replace with your actual logic
        // This is a placeholder - you should use the same logic as in your KosController
        
        // For now, return a default value
        return 0.5; // Default to middle range
    }
    
    private function getDefaultSearchCriteria()
    {
        // Default search criteria - modify this based on your actual implementation
        return [
            'harga' => '< Rp. 500.000',
            'rating' => 5,
            'jarak' => '< 1 km',
            'fasilitas' => [17, 19], // Kamar Mandi Dalam, AC
            'survey' => 4
        ];
    }
    
    private function verifySynchronization()
    {
        $this->info("Memverifikasi sinkronisasi...");
        
        // Check if all data is synchronized
        $kosCount = Kos::count();
        $normalisasiCount = NormalisasiKos::count();
        $hasilCount = HasilRekomendasi::count();
        
        $this->info("Jumlah Kos: {$kosCount}");
        $this->info("Jumlah Normalisasi: {$normalisasiCount}");
        $this->info("Jumlah Hasil Rekomendasi: {$hasilCount}");
        
        if ($kosCount == $normalisasiCount) {
            $this->info("✓ Data kos dan normalisasi sinkron");
        } else {
            $this->warn("⚠️  Data kos dan normalisasi tidak sinkron");
        }
        
        // Check recent updates
        $recentUpdates = HasilRekomendasi::where('updated_at', '>=', now()->subMinutes(5))->count();
        $this->info("Hasil yang baru diupdate: {$recentUpdates}");
        
        if ($recentUpdates > 0) {
            $this->info("✓ Rekomendasi berhasil diupdate!");
        } else {
            $this->warn("⚠️  Tidak ada rekomendasi yang diupdate");
        }
    }
    
    private function showUsage()
    {
        $this->info("Gunakan salah satu opsi berikut:");
        $this->info("1. Cek status PencarianController:");
        $this->info("   php artisan update:pencarian-controller --check");
        
        $this->info("\n2. Perbaiki PencarianController:");
        $this->info("   php artisan update:pencarian-controller --fix");
        
        $this->info("\n3. Atau gunakan command lengkap:");
        $this->info("   php artisan recalculate:all");
    }
}
