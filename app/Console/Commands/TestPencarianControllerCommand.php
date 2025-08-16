<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PencarianController;
use Illuminate\Http\Request;

class TestPencarianControllerCommand extends Command
{
    protected $signature = 'test:pencarian-controller {--harga=} {--rating=} {--jarak=} {--fasilitas=*} {--survey=}';
    protected $description = 'Test PencarianController directly';

    public function handle()
    {
        $this->info("=== TEST PENCARIAN CONTROLLER LANGSUNG ===");
        
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
        
        // Test PencarianController directly
        $this->info("\n=== TEST PENCARIAN CONTROLLER ===");
        
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
            
            $this->info("✓ PencarianController berhasil dijalankan!");
            
            // Display results
            $this->info("\n=== HASIL PENCARIAN CONTROLLER ===");
            $kosList = $result->items();
            
            foreach ($kosList as $index => $kos) {
                $rank = $index + 1;
                $this->info("{$rank}. {$kos->nama_kos} (ID: {$kos->id}) - Similarity: " . number_format($kos->similarity, 12));
            }
            
            // Compare with expected results
            $this->info("\n=== COMPARE WITH EXPECTED ===");
            $expectedResults = [
                ['id' => 30, 'nama' => 'Kost Putra Pak Amak', 'similarity' => 0.900],
                ['id' => 38, 'nama' => 'Kos Putri Merah Putih', 'similarity' => 0.880],
                ['id' => 40, 'nama' => 'Griya kost SK (kos Cewek)', 'similarity' => 0.840],
                ['id' => 36, 'nama' => 'Kost PUTRI AISYAH', 'similarity' => 0.710],
                ['id' => 31, 'nama' => 'Kos Putri Ar Rayyan', 'similarity' => 0.650],
                ['id' => 39, 'nama' => 'KOS MBAK TITIK', 'similarity' => 0.650],
                ['id' => 33, 'nama' => 'Rumah Kost Harapan Bunda', 'similarity' => 0.640],
                ['id' => 35, 'nama' => 'KOST PUTRI " JEPUN ASRI"', 'similarity' => 0.640],
            ];
            
            $matches = 0;
            foreach ($kosList as $index => $kos) {
                if (isset($expectedResults[$index])) {
                    $expected = $expectedResults[$index];
                    $roundedSimilarity = round($kos->similarity, 3);
                    $expectedSimilarity = round($expected['similarity'], 3);
                    
                    if ($kos->id == $expected['id'] && abs($roundedSimilarity - $expectedSimilarity) < 0.001) {
                        $matches++;
                        $this->info("✓ {$kos->nama_kos} - Expected: {$expectedSimilarity}, Got: {$roundedSimilarity}");
                    } else {
                        $this->warn("✗ {$kos->nama_kos} - Expected: {$expectedSimilarity}, Got: {$roundedSimilarity}");
                    }
                }
            }
            
            $this->info("\n=== VERIFIKASI AKHIR ===");
            $this->info("Matches: {$matches}/" . count($expectedResults));
            
            if ($matches == count($expectedResults)) {
                $this->info("🎉 PENCARIAN CONTROLLER 100% SESUAI!");
            } else {
                $this->warn("⚠️  Masih ada perbedaan pada " . (count($expectedResults) - $matches) . " kos");
            }
            
        } catch (\Exception $e) {
            $this->error("Error testing PencarianController: " . $e->getMessage());
        }
    }
}
