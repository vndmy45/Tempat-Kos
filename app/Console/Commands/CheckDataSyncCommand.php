<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;
use App\Models\Fasilitas;
use App\Models\HasilRekomendasi;
use Illuminate\Support\Facades\DB;

class CheckDataSyncCommand extends Command
{
    protected $signature = 'check:data-sync {--kos_id=} {--fix}';
    protected $description = 'Check data synchronization between admin panel and system calculations';

    public function handle()
    {
        $kosId = $this->option('kos_id');
        $fixMode = $this->option('fix');
        
        if ($kosId) {
            $this->checkSpecificKos($kosId, $fixMode);
        } else {
            $this->checkAllKos($fixMode);
        }
    }
    
    private function checkSpecificKos($kosId, $fixMode)
    {
        $this->info("=== CEK SINCHRONISASI DATA KOS ID {$kosId} ===");
        
        $kos = Kos::find($kosId);
        if (!$kos) {
            $this->error("Kos tidak ditemukan!");
            return;
        }
        
        $normalisasi = NormalisasiKos::where('id_kos', $kosId)->first();
        if (!$normalisasi) {
            $this->error("Data normalisasi tidak ditemukan!");
            return;
        }
        
        $this->info("Data Kos (Admin Panel):");
        $this->info("  Harga: " . number_format($kos->harga));
        $this->info("  Rating: " . ($kos->nilai_rating ?: 'Tidak ada'));
        $this->info("  Latitude: " . $kos->latitude);
        $this->info("  Longitude: " . $kos->longitude);
        $this->info("  Updated: " . $kos->updated_at);
        
        $this->info("\nData Normalisasi (Database):");
        $this->info("  Harga Normalized: " . $normalisasi->harga_normalized);
        $this->info("  Rating Normalized: " . $normalisasi->rating_normalized);
        $this->info("  Jarak Normalized: " . $normalisasi->jarak_normalized);
        $this->info("  Fasilitas: " . json_encode($normalisasi->fasilitas_normalized));
        $this->info("  Updated: " . $normalisasi->updated_at);
        
        // Check if normalization matches kos data
        $this->checkNormalizationAccuracy($kos, $normalisasi);
        
        if ($fixMode) {
            $this->fixKosNormalization($kos);
        }
    }
    
    private function checkAllKos($fixMode)
    {
        $this->info("=== CEK SINCHRONISASI SEMUA DATA KOS ===");
        
        $allKos = Kos::with('normalisasiKos')->get();
        $issues = [];
        $fixedCount = 0;
        
        foreach ($allKos as $kos) {
            if (!$kos->normalisasiKos) {
                $issues[] = "Kos ID {$kos->id}: Tidak ada data normalisasi";
                if ($fixMode) {
                    $this->fixKosNormalization($kos);
                    $fixedCount++;
                }
                continue;
            }
            
            $normalisasi = $kos->normalisasiKos;
            
            // Check harga normalization
            $expectedHarga = $this->calculateHargaNormalization($kos->harga);
            if ($expectedHarga != $normalisasi->harga_normalized) {
                $issues[] = "Kos ID {$kos->id}: Harga normalization salah (Expected: {$expectedHarga}, Actual: {$normalisasi->harga_normalized})";
                if ($fixMode) {
                    $this->fixKosNormalization($kos);
                    $fixedCount++;
                }
            }
            
            // Check rating normalization
            $expectedRating = $kos->nilai_rating ? $kos->nilai_rating / 5 : 0.5;
            if (abs($expectedRating - $normalisasi->rating_normalized) > 0.001) {
                $issues[] = "Kos ID {$kos->id}: Rating normalization salah (Expected: {$expectedRating}, Actual: {$normalisasi->rating_normalized})";
                if ($fixMode) {
                    $this->fixKosNormalization($kos);
                    $fixedCount++;
                }
            }
            
            // Check jarak normalization
            $expectedJarak = $this->calculateJarakNormalization($kos->latitude, $kos->longitude);
            if ($expectedJarak != $normalisasi->jarak_normalized) {
                $issues[] = "Kos ID {$kos->id}: Jarak normalization salah (Expected: {$expectedJarak}, Actual: {$normalisasi->jarak_normalized})";
                if ($fixMode) {
                    $this->fixKosNormalization($kos);
                    $fixedCount++;
                }
            }
        }
        
        if (empty($issues)) {
            $this->info("✓ Semua data sudah sinkron!");
        } else {
            $this->warn("⚠️  Ditemukan masalah:");
            foreach ($issues as $issue) {
                $this->warn("  " . $issue);
            }
            
            if ($fixMode) {
                $this->info("\n✓ Berhasil memperbaiki {$fixedCount} kos");
            } else {
                $this->info("\nGunakan --fix untuk memperbaiki masalah");
            }
        }
    }
    
    private function checkNormalizationAccuracy($kos, $normalisasi)
    {
        $this->info("\n=== VERIFIKASI AKURASI NORMALISASI ===");
        
        // Check harga normalization
        $expectedHarga = $this->calculateHargaNormalization($kos->harga);
        $this->info("Harga Normalization:");
        $this->info("  Raw: " . number_format($kos->harga));
        $this->info("  Expected: " . $expectedHarga);
        $this->info("  Actual: " . $normalisasi->harga_normalized);
        
        if ($expectedHarga != $normalisasi->harga_normalized) {
            $this->warn("  ⚠️  Harga normalization tidak sesuai!");
        } else {
            $this->info("  ✓ Harga normalization sesuai");
        }
        
        // Check rating normalization
        $expectedRating = $kos->nilai_rating ? $kos->nilai_rating / 5 : 0.5;
        $this->info("\nRating Normalization:");
        $this->info("  Raw: " . ($kos->nilai_rating ?: 'Tidak ada'));
        $this->info("  Expected: " . $expectedRating);
        $this->info("  Actual: " . $normalisasi->rating_normalized);
        
        if (abs($expectedRating - $normalisasi->rating_normalized) > 0.001) {
            $this->warn("  ⚠️  Rating normalization tidak sesuai!");
        } else {
            $this->info("  ✓ Rating normalization sesuai");
        }
        
        // Check jarak normalization
        $expectedJarak = $this->calculateJarakNormalization($kos->latitude, $kos->longitude);
        $this->info("\nJarak Normalization:");
        $this->info("  Raw: Lat {$kos->latitude}, Long {$kos->longitude}");
        $this->info("  Expected: " . $expectedJarak);
        $this->info("  Actual: " . $normalisasi->jarak_normalized);
        
        if ($expectedJarak != $normalisasi->jarak_normalized) {
            $this->warn("  ⚠️  Jarak normalization tidak sesuai!");
        } else {
            $this->info("  ✓ Jarak normalization sesuai");
        }
        
        // Check facilities
        $this->info("\nFacilities:");
        $kosFasilitas = $kos->fasilitas->pluck('id')->toArray();
        $this->info("  Raw: " . implode(', ', $kosFasilitas));
        $this->info("  Normalized: " . json_encode($normalisasi->fasilitas_normalized));
    }
    
    private function fixKosNormalization($kos)
    {
        $this->info("Memperbaiki normalisasi Kos ID {$kos->id}...");
        
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
        
        $this->info("  ✓ Harga: {$kos->harga} → {$hargaNormalized}");
        $this->info("  ✓ Rating: {$kos->nilai_rating} → {$ratingNormalized}");
        $this->info("  ✓ Jarak: {$jarakNormalized}");
        $this->info("  ✓ Fasilitas: " . implode(',', $kosFasilitas));
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
}
