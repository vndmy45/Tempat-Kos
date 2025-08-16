<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecalculateAllCommand extends Command
{
    protected $signature = 'recalculate:all {--user_id=} {--kos_id=} {--force}';
    protected $description = 'Recalculate all recommendations using latest data and ensure synchronization';

    public function handle()
    {
        $userId = $this->option('user_id');
        $kosId = $this->option('kos_id');
        $forceMode = $this->option('force');
        
        $this->info("=== PERHITUNGAN ULANG SEMUA REKOMENDASI ===");
        $this->info("Menggunakan data terbaru dari admin panel");
        
        // Step 1: Check and fix data synchronization
        $this->info("\n=== STEP 1: CEK SINCHRONISASI DATA ===");
        $this->checkAndFixDataSync();
        
        // Step 2: Recalculate recommendations
        $this->info("\n=== STEP 2: HITUNG ULANG REKOMENDASI ===");
        if ($kosId) {
            $this->recalculateSpecificKos($kosId, $userId);
        } elseif ($userId) {
            $this->recalculateUserRecommendations($userId);
        } else {
            $this->recalculateAllRecommendations();
        }
        
        // Step 3: Verify results
        $this->info("\n=== STEP 3: VERIFIKASI HASIL ===");
        $this->verifyResults();
        
        $this->info("\n=== SELESAI ===");
        $this->info("Semua rekomendasi telah dihitung ulang dengan data terbaru!");
    }
    
    private function checkAndFixDataSync()
    {
        $this->info("Memeriksa sinkronisasi data...");
        
        // Check for normalization issues
        $issues = $this->findNormalizationIssues();
        
        if (empty($issues)) {
            $this->info("✓ Semua data sudah sinkron");
            return;
        }
        
        $this->warn("⚠️  Ditemukan masalah sinkronisasi:");
        foreach ($issues as $issue) {
            $this->warn("  " . $issue);
        }
        
        $this->info("Memperbaiki masalah...");
        $this->fixAllNormalizationIssues();
    }
    
    private function findNormalizationIssues()
    {
        $issues = [];
        
        $allKos = Kos::with('normalisasiKos')->get();
        
        foreach ($allKos as $kos) {
            if (!$kos->normalisasiKos) {
                $issues[] = "Kos ID {$kos->id}: Tidak ada data normalisasi";
                continue;
            }
            
            $normalisasi = $kos->normalisasiKos;
            
            // Check harga normalization
            $expectedHarga = $this->calculateHargaNormalization($kos->harga);
            if ($expectedHarga != $normalisasi->harga_normalized) {
                $issues[] = "Kos ID {$kos->id}: Harga normalization salah (Expected: {$expectedHarga}, Actual: {$normalisasi->harga_normalized})";
            }
            
            // Check rating normalization
            $expectedRating = $kos->nilai_rating ? $kos->nilai_rating / 5 : 0.5;
            if (abs($expectedRating - $normalisasi->rating_normalized) > 0.001) {
                $issues[] = "Kos ID {$kos->id}: Rating normalization salah (Expected: {$expectedRating}, Actual: {$normalisasi->rating_normalized})";
            }
            
            // Check jarak normalization
            $expectedJarak = $this->calculateJarakNormalization($kos->latitude, $kos->longitude);
            if ($expectedJarak != $normalisasi->jarak_normalized) {
                $issues[] = "Kos ID {$kos->id}: Jarak normalization salah (Expected: {$expectedJarak}, Actual: {$normalisasi->jarak_normalized})";
            }
        }
        
        return $issues;
    }
    
    private function fixAllNormalizationIssues()
    {
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
    
    private function recalculateSpecificKos($kosId, $userId = null)
    {
        $this->info("Menghitung ulang rekomendasi untuk Kos ID {$kosId}");
        
        if ($userId) {
            $this->recalculateKosForUser($kosId, $userId);
        } else {
            // Recalculate for all users
            $users = User::all();
            foreach ($users as $user) {
                $this->recalculateKosForUser($kosId, $user->id);
            }
        }
    }
    
    private function recalculateUserRecommendations($userId)
    {
        $this->info("Menghitung ulang semua rekomendasi untuk User ID {$userId}");
        
        // Get user's search criteria (you can modify this based on your actual implementation)
        $searchCriteria = $this->getDefaultSearchCriteria();
        
        $this->recalculateForUser($userId, $searchCriteria);
    }
    
    private function recalculateAllRecommendations()
    {
        $this->info("Menghitung ulang SEMUA rekomendasi untuk SEMUA user...");
        
        $users = User::all();
        $totalUsers = $users->count();
        
        $this->info("Total users: {$totalUsers}");
        
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();
        
        $recalculatedCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            try {
                $searchCriteria = $this->getDefaultSearchCriteria();
                $this->recalculateForUser($user->id, $searchCriteria);
                $recalculatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error for user {$user->id}: " . $e->getMessage());
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✓ Recalculated: {$recalculatedCount}, Errors: {$errorCount}");
    }
    
    private function recalculateKosForUser($kosId, $userId)
    {
        $this->info("  Recalculating Kos ID {$kosId} for User ID {$userId}");
        
        // Get search criteria
        $searchCriteria = $this->getDefaultSearchCriteria();
        
        // Calculate similarity
        $similarity = $this->calculateSimilarity($kosId, $searchCriteria);
        
        // Update or create result
        HasilRekomendasi::updateOrCreate(
            [
                'id_user' => $userId,
                'id_kos' => $kosId
            ],
            [
                'nilai_similarity' => $similarity
            ]
        );
        
        $this->info("    Similarity: " . number_format($similarity, 12));
    }
    
    private function recalculateForUser($userId, $searchCriteria)
    {
        $this->info("  Recalculating for User ID {$userId}");
        
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
                
                $this->info("    Kos ID {$kos->id}: " . number_format($similarity, 12));
            } catch (\Exception $e) {
                $this->error("    Error Kos ID {$kos->id}: " . $e->getMessage());
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
    
    private function verifyResults()
    {
        $this->info("Memverifikasi hasil perhitungan...");
        
        // Check if all recommendations are updated
        $totalResults = HasilRekomendasi::count();
        $this->info("Total hasil rekomendasi: {$totalResults}");
        
        // Check recent updates
        $recentResults = HasilRekomendasi::where('updated_at', '>=', now()->subMinutes(5))->count();
        $this->info("Hasil yang baru diupdate: {$recentResults}");
        
        if ($recentResults > 0) {
            $this->info("✓ Rekomendasi berhasil diupdate!");
        } else {
            $this->warn("⚠️  Tidak ada rekomendasi yang diupdate");
        }
    }
}
