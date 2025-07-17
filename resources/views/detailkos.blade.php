@extends('layouts.app')
@section('content')
<div class="container mt-5 py-4">
    @php
    $dariRekomendasi = request()->has('from') && str_contains(request('from'), 'metode=rekomendasi');
    $sudahSurvey = auth()->check() ? \App\Models\SurveyKepuasan::where('id_user', auth()->id())->where('id_kos', $kos->id)->exists() : false;
@endphp

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

    <div class="mb-4">
        <a href="{{ session('kos_back_url', url('/')) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Judul dan Navigasi -->
    <div class="mb-4 text-center">
        <h2 class="text-primary fw-bold"><i class="fas fa-home"></i> Detail Kos</h2>
    </div>

    <!-- Nama Kos -->
    <h3 class="text-primary fw-semibold mb-3">{{ $kos->nama_kos }}</h3>

    <!-- Gambar Kos -->
    <div class="col-12">
    <div class="row">
        @forelse ($kos->gambarKos as $gambar)
            <div class="col-md-3 col-12 mb-4">
                <div style="height: 220px; overflow: hidden; border-radius: 0.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                    <img src="{{ $gambar->link_foto }}"
                         alt="{{ $gambar->nama_foto }}"
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
        @empty
            <p class="text-muted">Belum ada gambar.</p>
        @endforelse
    </div>
</div>

    <div class="row g-4">
        <!-- Kolom Informasi Kiri -->
        <div class="col-md-6">
            <div class="mb-2"><strong>Kos {{ $kos->jenis_kost }}</strong></div>
            <div class="mb-2">Jarak: <strong>{{ number_format($kos->jarak, 2) }} km</strong></div>

            <div class="mb-3">
                <strong>Fasilitas Kos:</strong>
                <ul class="mb-0">
                    @foreach($kos->fasilitas as $fasilitas)
                        <li>{{ $fasilitas->nama_fasilitas }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="mb-2">
                <strong>Rating:</strong>
                {{ str_repeat('⭐', floor($kos->nilai_rating)) }} 
                <span class="text-muted">({{ number_format($kos->nilai_rating, 1) ?? '-' }})</span>
            </div>

            <div class="mb-3">
                <strong>Harga:</strong>
                <span class="text-danger fs-5 fw-bold">Rp. {{ number_format($kos->harga, 0, ',', '.') }}</span>
            </div>

            <div class="text-success fw-semibold">
                Tersedia {{ $kos->jumlah_kamar ?? '2' }} kamar lagi!!
            </div>
        </div>

        <!-- Kolom Informasi Kanan -->
        <div class="col-md-6">
            <div class="mb-3">
                <strong>No. Yang dapat dihubungi:</strong><br>
                <span class="fs-5 fw-bold text-dark">{{ $kos->kontak_pemilik }}</span>
            </div>

            <div class="mb-3">
                <strong>Berikan penilaian:</strong><br>
                <form method="POST" action="{{ route('komentar.store') }}">
                    @csrf
                    <input type="hidden" name="id_kos" value="{{ $kos->id }}">
                    <div class="mb-2">
                        <select name="rating" class="form-select w-50 d-inline-block" required>
                            <option value="">Pilih Rating</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ str_repeat('⭐', $i) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="isi_komentar" class="form-control" placeholder="Tulis ulasan..." required></textarea>
                    </div>
                    <button class="btn btn-primary">Kirim</button>
                </form>
            </div>

            <div>
                <strong>Ulasan pengguna sebelumnya:</strong>
                <div class="border p-2 rounded bg-light mt-2" style="max-height: 200px; overflow-y: auto;">
                    @forelse($kos->komentar as $komentar)
                        <div class="mb-2">
                            <div class="fw-semibold">{{ $komentar->user->name ?? 'Pengguna' }}</div>
                            <div class="text-warning">{{ str_repeat('⭐', floor($komentar->rating)) }}</div>
                            <div class="text-muted">{{ $komentar->isi_komentar }}</div>
                        </div>
                    @empty
                        <p class="text-muted">Belum ada komentar.</p>
                    @endforelse
                </div>
            </div>
            @if($dariRekomendasi && !$sudahSurvey)
                <div class="mt-4 p-4 border rounded bg-light">
                    <h5 class="mb-3 text-primary">Seberapa cocok rekomendasi ini dengan keinginan Anda?</h5>
                    <form method="POST" action="{{ route('survey.store') }}">
                        @csrf
                        <input type="hidden" name="id_kos" value="{{ $kos->id }}">

                        <div class="mb-3">
                            <label class="form-label">Skor Kepuasan (1 = tidak cocok, 5 = sangat cocok):</label>
                            <select name="skor" class="form-select w-25" required>
                                <option value="">Pilih Skor</option>
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} - {{ str_repeat('⭐', $i) }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Komentar (opsional)</label>
                            <textarea name="komentar" class="form-control" rows="3" placeholder="Tuliskan kesan Anda..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Kirim Survey</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


