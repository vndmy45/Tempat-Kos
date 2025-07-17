@extends('layouts.app')
@section('content')
<div class="search-page">
    <div class="search-header position-relative">
        <div class="container">
            <div class="d-flex align-items-center justify-content-center mb-4">
                <h2 class="text-white mb-0">Rekomendasi Pencarian Kos</h2>
                <i class="fas fa-search text-white ms-3 search-icon"></i>
            </div>
            
            <!-- Filter Section -->
            <form action="{{ route('pencarian.index') }}" method="GET" id="filterForm">
                <div class="filter-section bg-white p-3 rounded">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Harga</label>
                            <select class="form-select" name="harga">
                                <option value="">Pilih Harga</option>
                                <option value="< Rp. 500.000" {{ request('harga') == '< Rp. 500.000' ? 'selected' : '' }}>< Rp. 500.000</option>
                                <option value="Rp. 500.000 - Rp. 1.000.000" {{ request('harga') == 'Rp. 500.000 - Rp. 1.000.000' ? 'selected' : '' }}>Rp. 500.000 - Rp. 1.000.000</option>
                                <option value="> Rp. 1.000.000" {{ request('harga') == '> Rp. 1.000.000' ? 'selected' : '' }}>> Rp. 1.000.000</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fasilitas</label>
                            <select class="selectpicker w-100" name="fasilitas[]" multiple data-style="btn-outline-secondary">
                                @foreach($fasilitas as $item)
                                    <option value="{{ $item->id }}" {{ collect(request('fasilitas'))->contains($item->id) ? 'selected' : '' }}>
                                        {{ $item->nama_fasilitas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rating</label>
                            <select class="form-select" name="rating">
                                <option value="">Pilih Rating</option>
                                @foreach($ratings as $rating)
                                    <option value="{{ $rating }}" {{ request('rating') == $rating ? 'selected' : '' }}>
                                        {{ str_repeat('⭐', $rating) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Jarak</label>
                            <select class="form-select" name="jarak">
                                <option value="">Pilih Jarak</option>
                                <option value="< 1 km" {{ request('jarak') == '< 1 km' ? 'selected' : '' }}>< 1 km</option>
                                <option value="1 - 3 km" {{ request('jarak') == '1 - 3 km' ? 'selected' : '' }}>1 - 3 km</option>
                                <option value="> 3 km" {{ request('jarak') == '> 3 km' ? 'selected' : '' }}>> 3 km</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 d-flex justify-content-center gap-3">
                            <button type="submit" name="metode" value="filter" class="btn btn-primary">Cari</button>
                            <button type="submit" name="metode" value="rekomendasi" class="btn btn-primary">Rekomendasi</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hasil Pencarian -->
<div class="search-results py-5">
    <div class="container">
        @if(request('metode') === 'rekomendasi')
            <h3 class="mb-4">Hasil Rekomendasi Kos Untukmu</h3>
        @else
            <h3 class="mb-4">Hasil Pencarian Kos Sesuai Filter</h3>
        @endif
        
        <div class="row g-4">
            @forelse($kos_list as $kos)
            <div class="col-md-3">
                <div class="card kost-card">
                    <img src="{{ $kos->gambarKos->first() ? asset($kos->gambarKos->first()->link_foto) : asset('default.jpg') }}" class="card-img-top" alt="{{ $kos->nama_kos }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0">{{ $kos->nama_kos }}</h5>
                            <div class="rating">
                                @for($i = 0; $i < floor($kos->nilai_rating); $i++)
                                    ⭐
                                @endfor
                                <small class="text-muted">({{ number_format($kos->nilai_rating, 1) }})</small>
                            </div>
                        </div>
                        <p class="card-text mb-2">Rp. {{ number_format($kos->harga, 0, ',', '.') }}</p>
                        <small class="text-muted">Jarak: {{ number_format($kos->jarak, 2) }} km</small>
                        <small>Similarity: {{ number_format($kos->similarity, 4) }}</small>
                        <a href="{{ route('user.kos.show', ['id' => $kos->id, 'from' => request()->fullUrl()]) }}" class="btn btn-primary w-100">Detail</a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="alert alert-info">
                    Tidak ada kos yang ditemukan
                </div>
            </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $kos_list->links() }}
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        $('.selectpicker').selectpicker();
    });
</script>
@endsection