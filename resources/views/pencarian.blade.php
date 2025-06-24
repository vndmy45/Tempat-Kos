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
            <form action="{{ route('pencarian.filter') }}" method="GET" id="filterForm">
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
                            <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                @foreach($fasilitas as $f)
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            name="fasilitas[]" 
                                            value="{{ $f->id }}" 
                                            id="fasilitas_{{ $f->id }}"
                                            {{ is_array(request('fasilitas')) && in_array($f->id, request('fasilitas')) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fasilitas_{{ $f->id }}">
                                            {{ $f->nama_fasilitas }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rating</label>
                            <select class="form-select" name="rating">
                                <option value="">Pilih Rating</option>
                                <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>⭐⭐⭐⭐⭐</option>
                                <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>⭐⭐⭐⭐</option>
                                <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>⭐⭐⭐</option>
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
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Cari</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hasil Pencarian -->
    <div class="search-results py-5">
        <div class="container">
            <h3 class="mb-4">Rekomendasi Kost Terpopuler Untukmu</h3>
            
            <div class="row g-4">
                @forelse($kos_list as $kos)
                <div class="col-md-3">
                    <div class="card kost-card">
                        <img src="{{ asset('storage/' . $kos->gambar) }}" class="card-img-top" alt="{{ $kos->nama }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">{{ $kos->nama }}</h5>
                                <div class="rating">
                                    @for($i = 0; $i < $kos->rating; $i++)
                                        ⭐
                                    @endfor
                                </div>
                            </div>
                            <p class="card-text mb-2">Rp. {{ number_format($kos->harga, 0, ',', '.') }}</p>
                            <a href="{{ route('kos.show', $kos->id) }}" class="btn btn-primary w-100">Detail</a>
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
</div>

<script>
    $(document).ready(function () {
        $('.selectpicker').selectpicker();
    });
</script>
@endsection