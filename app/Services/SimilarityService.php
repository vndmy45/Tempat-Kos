<?php

namespace App\Services;

use App\Models\Fasilitas;
use App\Models\NormalisasiKos;
use App\Models\SurveyKepuasan;

class SimilarityService
{
    public static function buildUserVector($harga, $rating, $jarak, $fasilitas, $survey)
{
    // Ambil struktur fasilitas_normalized dari salah satu data kos
    $sampleKos = NormalisasiKos::first();
    $jumlahFasilitas = 0;
    $fasilitasVector = [];

    if ($sampleKos && is_array($sampleKos->fasilitas_normalized)) {
        $jumlahFasilitas = count($sampleKos->fasilitas_normalized);

        // Build sesuai urutan sampleKos
        for ($i = 0; $i < $jumlahFasilitas; $i++) {
            $fasilitasVector[] = (
                is_array($fasilitas) && in_array($i, $fasilitas)
            ) ? 1 : 0;
        }
    }

    return array_merge([$harga,$rating, $jarak], $fasilitasVector, [$survey]);
}

public static function buildKosVector($data)
{
    // Pastikan fasilitas_normalized ada
    $fasilitas = is_array($data->fasilitas_normalized)
        ? $data->fasilitas_normalized
        : [];

    return array_merge([
        $data->harga_normalized,
        $data->rating_normalized,
        $data->jarak_normalized,
    ], $fasilitas, [
        $data->survey_normalized ?? 0.5
    ]);
}

    public static function cosineSimilarity(array $vectorA, array $vectorB)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        $length = min(count($vectorA), count($vectorB));
        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += pow($vectorA[$i], 2);
            $magnitudeB += pow($vectorB[$i], 2);
        }

        return ($magnitudeA && $magnitudeB) ? $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB)) : 0;
    }
}
