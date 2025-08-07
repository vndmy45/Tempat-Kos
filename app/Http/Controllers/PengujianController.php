<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilRekomendasi;
use App\Models\SurveyKepuasan;
use Illuminate\Support\Facades\Auth;

class PengujianController extends Controller
{
    // Fungsi untuk menghitung Mean Absolute Error (MAE)
    public function mae()
    {
        // Ambil ID user yang sedang login
        $userId = Auth::id();

        // Ambil semua data hasil rekomendasi berdasarkan user
        $rekomendasi = HasilRekomendasi::where('id_user', $userId)->get();

        // Inisialisasi nilai total error dan jumlah data
        $totalError = 0;
        $jumlahData = 0;

        // Array untuk menyimpan detail hasil perhitungan MAE tiap kos
        $detail = [];

        // Loop semua data rekomendasi
        foreach ($rekomendasi as $item) {
            // Cari data survey yang cocok dengan user dan kos, ambil yang paling baru
            $survey = SurveyKepuasan::where('id_user', $userId)
                ->where('id_kos', $item->id_kos)
                ->orderByDesc('created_at')
                ->first();

            // Jika ada data survey untuk kos tersebut
            if ($survey) {
                // Ambil nilai prediksi dari sistem (similarity), dibulatkan 4 angka desimal
                $prediksi = round($item->nilai_similarity, 4);

                // Ambil skor aktual dari survey, dinormalisasi (1–5 jadi 0.2–1)
                $aktual = $survey->skor / 5;

                // Hitung selisih absolut antara prediksi dan aktual
                $selisih = abs($prediksi - $aktual);

                // Tambahkan ke total error dan jumlah data
                $totalError += $selisih;
                $jumlahData++;

                // Simpan detail pengujian per kos
                $detail[] = [
                    'id_kos' => $item->id_kos,
                    'prediksi' => $prediksi,
                    'aktual' => $survey->skor, // nilai asli (1–5)
                    'normal_aktual' => round($aktual, 4), // nilai yang telah dinormalisasi
                    'selisih' => round($selisih, 4),
                ];
            }
        }

        // Hitung nilai MAE (jika ada data yang dihitung)
        $mae = $jumlahData > 0 ? $totalError / $jumlahData : null;

        // Kirim hasil ke view untuk ditampilkan ke pengguna
        return view('pengujian.mae', compact('mae', 'jumlahData', 'detail'));
    }
}
