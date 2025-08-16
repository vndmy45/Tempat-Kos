<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Http\Request;

class SyncPerfectSimilarityCommand extends Command
{
    protected $signature = 'sync:perfect-similarity {--user_id=1} {--force}';
    protected $description = 'Perfectly sync similarity values between database and controller';

    public function handle()
    {
        $userId = $this->option('user_id');
        $forceMode = $this->option('force');
        
        $this->info("=== SINCHRONISASI SEMPURNA SIMILARITY ===");
        $this->info("User ID: {$userId}");
        
        // Step 1: Check current differences
        $this->info("\n=== STEP 1: CEK PERBEDAAN SAAT INI ===");
        $differences = $this->checkCurrentDifferences($userId);
        
        if (empty($differences)) {
            $this->info("✓ Semua similarity sudah 100% sama!");
            return;
        }
        
        $this->warn("⚠️  Ditemukan " . count($differences) . " kos dengan perbedaan similarity");
        
        // Step 2: Show differences
        $this->showDifferences($differences);
        
        // Step 3: Fix differences
        if ($forceMode) {
            $this->info("\n=== STEP 2: PERBAIKAN OTOMATIS ===");
            $this->fixAllDifferences($userId, $differences);
        } else {
            $this->info("\nGunakan --force untuk memperbaiki otomatis");
            $this->showManualFixInstructions($differences);
        }
    }
    
    private function checkCurrentDifferences($userId)
    {
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
                
                if ($difference > 0.0001) { // Tolerance 0.0001
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
        
        return $differences;
    }
    
    private function showDifferences($differences)
    {
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
    
    private function fixAllDifferences($userId, $differences)
    {
        $fixedCount = 0;
        $errorCount = 0;
        
        $progressBar = $this->output->createProgressBar(count($differences));
        $progressBar->start();
        
        foreach ($differences as $diff) {
            try {
                $this->fixSimilarity($userId, $diff['id']);
                $fixedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error fixing Kos ID {$diff['id']}: " . $e->getMessage());
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✓ Berhasil memperbaiki {$fixedCount} kos");
        if ($errorCount > 0) {
            $this->error("✗ Error pada {$errorCount} kos");
        }
        
        // Verify fixes
        $this->info("\n=== VERIFIKASI PERBAIKAN ===");
        $remainingDifferences = $this->checkCurrentDifferences($userId);
        
        if (empty($remainingDifferences)) {
            $this->info("✓ Semua similarity sudah 100% sama!");
        } else {
            $this->warn("⚠️  Masih ada " . count($remainingDifferences) . " kos dengan perbedaan");
        }
    }
    
    private function fixSimilarity($userId, $kosId)
    {
        $request = $this->getDefaultRequest();
        $newSimilarity = $this->calculateSimilarity($kosId, $request);
        
        // Update database with exact controller value
        HasilRekomendasi::where('id_user', $userId)
            ->where('id_kos', $kosId)
            ->update(['nilai_similarity' => $newSimilarity]);
    }
    
    private function showManualFixInstructions($differences)
    {
        $this->info("\n=== CARA MANUAL PERBAIKAN ===");
        $this->info("Untuk memperbaiki otomatis, jalankan:");
        $this->info("  php artisan sync:perfect-similarity --force");
        
        $this->info("\nAtau perbaiki manual dengan command:");
        foreach ($differences as $diff) {
            $this->info("  php artisan sync:perfect-similarity --force --user_id=1");
            break; // Show only one example
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
