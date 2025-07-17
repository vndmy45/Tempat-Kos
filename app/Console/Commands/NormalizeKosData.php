<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kos;
use App\Models\Fasilitas;
use App\Models\NormalisasiKos;

class NormalizeKosData extends Command
{
    protected $signature = 'normalize:kos';
    protected $description = 'Normalisasi data kos dan simpan ke tabel normalisasi_kos';

    public function handle()
    {
        $this->info('Mulai proses normalisasi data kos...');

        $kosList = Kos::with('fasilitas')->get();

        if ($kosList->isEmpty()) {
            $this->warn('Tidak ada data kos untuk dinormalisasi.');
            return;
        }

        // Ambil nilai min dan max dari atribut
        $minHarga = $kosList->min('harga');
        $maxHarga = $kosList->max('harga');
        $minRating = $kosList->min('nilai_rating');
        $maxRating = $kosList->max('nilai_rating');

        $latitudeRef = -8.320569;
        $longitudeRef = 114.233245;

        $jarakList = $kosList->map(function ($kos) use ($latitudeRef, $longitudeRef) {
            return $this->hitungJarak($latitudeRef, $longitudeRef, $kos->latitude, $kos->longitude);
        });

        $minJarak = $jarakList->min();
        $maxJarak = $jarakList->max();

        $allFasilitas = Fasilitas::pluck('id')->toArray();

        foreach ($kosList as $kos) {
            $hargaNormalized = $this->minMax($kos->harga, $minHarga, $maxHarga);
            $ratingNormalized = $this->minMax($kos->nilai_rating, $minRating, $maxRating);
            $jarak = $this->hitungJarak($latitudeRef, $longitudeRef, $kos->latitude, $kos->longitude);
            $jarakNormalized = $this->minMax($jarak, $minJarak, $maxJarak);

            $fasilitasKos = $kos->fasilitas->pluck('id')->toArray();
            $allFasilitasIndexed = Fasilitas::orderBy('index')->get(['id', 'index']);
            $maxIndex = $allFasilitasIndexed->max('index');

            $fasilitasVector = $allFasilitasIndexed->map(function ($fasilitas) use ($fasilitasKos, $maxIndex) {
                return in_array($fasilitas->id, $fasilitasKos)
                    ? $fasilitas->index / $maxIndex
                    : 0;
            })->toArray();


            NormalisasiKos::updateOrCreate(
                ['id_kos' => $kos->id],
                [
                    'harga_normalized' => round($hargaNormalized, 3),
                    'rating_normalized' => round($ratingNormalized, 3),
                    'jarak_normalized' => round($jarakNormalized, 3),
                    'fasilitas_normalized' => $fasilitasVector
                ]
            );
        }

        $this->info('Data normalisasi kos berhasil diperbarui.');
    }

    private function minMax($value, $min, $max)
    {
        return ($max - $min) != 0 ? ($value - $min) / ($max - $min) : 0;
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $radius * $c;
    }
}
