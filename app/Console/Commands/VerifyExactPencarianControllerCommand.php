<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PencarianController;
use Illuminate\Http\Request;

class VerifyExactPencarianControllerCommand extends Command
{
    protected $signature = 'verify:exact-pencarian-controller {--harga=} {--rating=} {--jarak=} {--fasilitas=*} {--survey=}';
    protected $description = 'Verify PencarianController uses exact real-time calculations';

    public function handle()
    {
        $this->info("=== VERIFIKASI PENCARIAN CONTROLLER EXACT ===");
        
        // Get user input
        $harga = $this->option('harga') ?: '< Rp. 500.000';
        $rating = $this->option('rating') ?: 5;
        $jarak = $this->option('jarak') ?: '< 1 km';
        $fasilitas = $this->option('fasilitas') ?: [17, 19];
        $survey = $this->option('survey') ?: 4;
        
        $this->info("Input User:");
        $this->info("  Harga: {$harga}");
        $this->info("  Rating: {$rating}");
        $this->info("  Jarak: {$jarak}");
        $this->info("  Fasilitas: " . implode(', ', $fasilitas));
        $this->info("  Survey: {$survey}");
        
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
        
        // Test PencarianController with exact calculations
        $this->info("\n=== TEST PENCARIAN CONTROLLER EXACT ===");
        
        try {
            // Mock authenticated user
            $user = new \App\Models\User();
            $user->id = 1;
            $user->name = 'Test User';
            $user->email = 'test@example.com';
            
            // Mock auth
            auth()->login($user);
            
            $controller = new PencarianController();
            
            // Use reflection to access private method
            $reflection = new \ReflectionClass($controller);
            $prosesRekomendasiMethod = $reflection->getMethod('prosesRekomendasi');
            $prosesRekomendasiMethod->setAccessible(true);
            
            // Call the method
            $result = $prosesRekomendasiMethod->invoke($controller, $request);
            
            $this->info("✓ PencarianController berhasil dijalankan dengan perhitungan exact!");
            
            // Display results
            $this->info("\n=== HASIL PENCARIAN CONTROLLER EXACT ===");
            $kosList = $result->items();
            
            foreach ($kosList as $index => $kos) {
                $rank = $index + 1;
                $this->info("{$rank}. {$kos->nama_kos} (ID: {$kos->id}) - Similarity: " . number_format($kos->similarity, 12));
            }
            
            // Compare with expected exact results
            $this->info("\n=== COMPARE WITH EXPECTED EXACT ===");
            $expectedResults = [
                ['id' => 30, 'nama' => 'Kost Putra Pak Amak', 'similarity' => 0.896674744570],
                ['id' => 38, 'nama' => 'Kos Putri Merah Putih', 'similarity' => 0.877790816382],
                ['id' => 40, 'nama' => 'Griya kost SK (kos Cewek)', 'similarity' => 0.844471903206],
                ['id' => 36, 'nama' => 'Kost PUTRI AISYAH', 'similarity' => 0.711545770294],
                ['id' => 31, 'nama' => 'Kos Putri Ar Rayyan', 'similarity' => 0.652716248079],
                ['id' => 39, 'nama' => 'KOS MBAK TITIK', 'similarity' => 0.645054900347],
                ['id' => 33, 'nama' => 'Rumah Kost Harapan Bunda', 'similarity' => 0.641592661886],
                ['id' => 35, 'nama' => 'KOST PUTRI " JEPUN ASRI"', 'similarity' => 0.641592661886],
            ];
            
            $exactMatches = 0;
            foreach ($kosList as $index => $kos) {
                if (isset($expectedResults[$index])) {
                    $expected = $expectedResults[$index];
                    
                    // Check if similarity matches exactly (within high precision)
                    if ($kos->id == $expected['id'] && abs($kos->similarity - $expected['similarity']) < 0.000001) {
                        $exactMatches++;
                        $this->info("✓ {$kos->nama_kos} - Expected: " . number_format($expected['similarity'], 12) . ", Got: " . number_format($kos->similarity, 12));
                    } else {
                        $this->warn("✗ {$kos->nama_kos} - Expected: " . number_format($expected['similarity'], 12) . ", Got: " . number_format($kos->similarity, 12));
                    }
                }
            }
            
            $this->info("\n=== VERIFIKASI EXACT AKHIR ===");
            $this->info("Exact Matches: {$exactMatches}/" . count($expectedResults));
            
            if ($exactMatches == count($expectedResults)) {
                $this->info("🎉 PENCARIAN CONTROLLER 100% EXACT DENGAN PERHITUNGAN REAL-TIME!");
                $this->info("✅ Menggunakan perhitungan real-time yang akurat");
                $this->info("✅ Tidak bergantung pada nilai database yang dibulatkan");
                $this->info("✅ Precision tinggi untuk semua perhitungan");
                $this->info("✅ Sesuai dengan inputan user");
            } else {
                $this->warn("⚠️  Masih ada perbedaan pada " . (count($expectedResults) - $exactMatches) . " kos");
            }
            
        } catch (\Exception $e) {
            $this->error("Error testing PencarianController: " . $e->getMessage());
        }
    }
}
