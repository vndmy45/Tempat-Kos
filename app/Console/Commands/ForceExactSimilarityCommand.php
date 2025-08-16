<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForceExactSimilarityCommand extends Command
{
    protected $signature = 'force:exact-similarity {--user_id=1} {--kos_id=} {--all}';
    protected $description = 'Force database to use exact controller similarity values';

    public function handle()
    {
        $userId = $this->option('user_id');
        $kosId = $this->option('kos_id');
        $allMode = $this->option('all');
        
        $this->info("=== FORCE EXACT SIMILARITY ===");
        $this->info("User ID: {$userId}");
        
        if ($kosId) {
            $this->forceExactSimilarity($userId, $kosId);
        } elseif ($allMode) {
            $this->forceAllExactSimilarity($userId);
        } else {
            $this->showUsage();
        }
    }
    
    private function forceExactSimilarity($userId, $kosId)
    {
        $this->info("Memaksa similarity Kos ID {$kosId} menjadi exact...");
        
        $request = $this->getDefaultRequest();
        $exactSimilarity = $this->calculateExactSimilarity($kosId, $request);
        
        $this->info("Exact Similarity: " . number_format($exactSimilarity, 12));
        
        // Update database with exact value
        $updated = HasilRekomendasi::where('id_user', $userId)
            ->where('id_kos', $kosId)
            ->update(['nilai_similarity' => $exactSimilarity]);
            
        if ($updated) {
            $this->info("✓ Database berhasil diupdate!");
            
            // Verify
            $newDbValue = HasilRekomendasi::where('id_user', $userId)
                ->where('id_kos', $kosId)
                ->value('nilai_similarity');
                
            $this->info("New DB Value: " . number_format($newDbValue, 12));
            
            $difference = abs($exactSimilarity - $newDbValue);
            if ($difference < 0.0001) {
                $this->info("✓ Similarity sudah 100% sama!");
            } else {
                $this->warn("⚠️  Masih ada perbedaan: " . number_format($difference, 12));
            }
        } else {
            $this->error("✗ Gagal mengupdate database!");
        }
    }
    
    private function forceAllExactSimilarity($userId)
    {
        $this->info("Memaksa SEMUA similarity menjadi exact...");
        
        $allKos = Kos::all();
        $updatedCount = 0;
        $errorCount = 0;
        
        $progressBar = $this->output->createProgressBar($allKos->count());
        $progressBar->start();
        
        $request = $this->getDefaultRequest();
        
        foreach ($allKos as $kos) {
            try {
                $exactSimilarity = $this->calculateExactSimilarity($kos->id, $request);
                
                $updated = HasilRekomendasi::where('id_user', $userId)
                    ->where('id_kos', $kos->id)
                    ->update(['nilai_similarity' => $exactSimilarity]);
                    
                if ($updated) {
                    $updatedCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error Kos ID {$kos->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✓ Berhasil mengupdate {$updatedCount} kos");
        if ($errorCount > 0) {
            $this->error("✗ Error pada {$errorCount} kos");
        }
        
        // Final verification
        $this->info("\n=== VERIFIKASI FINAL ===");
        $this->verifyAllSimilarity($userId);
    }
    
    private function verifyAllSimilarity($userId)
    {
        $allKos = Kos::all();
        $differences = [];
        
        $request = $this->getDefaultRequest();
        
        foreach ($allKos as $kos) {
            $dbSimilarity = HasilRekomendasi::where('id_user', $userId)
                ->where('id_kos', $kos->id)
                ->value('nilai_similarity');
                
            if ($dbSimilarity) {
                $controllerSimilarity = $this->calculateExactSimilarity($kos->id, $request);
                $difference = abs($dbSimilarity - $controllerSimilarity);
                
                if ($difference > 0.0001) {
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
            $this->info("🎉 SEMUA SIMILARITY SUDAH 100% SAMA!");
        } else {
            $this->warn("⚠️  Masih ada " . count($differences) . " kos dengan perbedaan:");
            foreach ($differences as $diff) {
                $this->info("  Kos ID {$diff['id']}: Selisih " . number_format($diff['difference'], 12));
            }
        }
    }
    
    private function calculateExactSimilarity($kosId, Request $request)
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
        
        // Calculate similarity with maximum precision
        return $this->cosineSimilarityExact($userVector, $kosVector);
    }
    
    private function cosineSimilarityExact(array $vectorA, array $vectorB)
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

        // Use maximum precision
        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
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
    
    private function showUsage()
    {
        $this->info("Gunakan salah satu opsi berikut:");
        $this->info("1. Force similarity untuk kos tertentu:");
        $this->info("   php artisan force:exact-similarity --kos_id=30");
        
        $this->info("\n2. Force similarity untuk semua kos:");
        $this->info("   php artisan force:exact-similarity --all");
        
        $this->info("\n3. Force similarity untuk user tertentu:");
        $this->info("   php artisan force:exact-similarity --all --user_id=2");
    }
}
