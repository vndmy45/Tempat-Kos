<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SurveyKepuasan;

class SurveyKepuasanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_kos' => 'required|exists:kos,id',
            'skor' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string',
        ]);

        SurveyKepuasan::create([
            'id_user' => Auth::id(),
            'id_kos' => $request->id_kos,
            'skor' => $request->skor,
            'komentar' => $request->komentar,
        ]);

        return back()->with('success', 'Terima kasih atas penilaian Anda!');
    }
}
