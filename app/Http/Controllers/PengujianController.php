<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilRekomendasi;
use App\Models\SurveyKepuasan;
use Illuminate\Support\Facades\Auth;

class PengujianController extends Controller
{
    public function mae()
    {
        $userId = Auth::id();

        $rekomendasi = HasilRekomendasi::where('id_user', $userId)->get();
        $totalError = 0;
        $jumlahData = 0;
        $detail = [];

        foreach ($rekomendasi as $item) {
            $survey = SurveyKepuasan::where('id_user', $userId)
                ->where('id_kos', $item->id_kos)
                ->orderByDesc('created_at')
                ->first();

            if ($survey) {
                $prediksi = round($item->nilai_similarity, 4); // prediksi dari sistem
                $aktual = $survey->skor / 5; // skor aktual dinormalisasi
                $selisih = abs($prediksi - $aktual);

                $totalError += $selisih;
                $jumlahData++;

                $detail[] = [
                    'id_kos' => $item->id_kos,
                    'prediksi' => $prediksi,
                    'aktual' => $survey->skor,
                    'normal_aktual' => round($aktual, 4),
                    'selisih' => round($selisih, 4),
                ];
            }
        }

        $mae = $jumlahData > 0 ? $totalError / $jumlahData : null;

        return view('pengujian.mae', compact('mae', 'jumlahData', 'detail'));
    }
}
