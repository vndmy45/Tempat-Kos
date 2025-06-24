@extends('layouts.sidebar')
@section('content')

<h1 class="judul">Beranda</h1>
<div class="welcome-card">
    <h3>Selamat Datang di Sistem Rekomendasi Tempat Kos</h3>
</div>


<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-white p-3 card-summary">
            <div class="d-flex align-items-center">
                <i class="bi bi-currency-dollar fs-2 text-primary me-3"></i>
                <div>
                    <p class="mb-0 text-muted">Harga Kos (Rp)</p>
                    <h5>Rp{{ number_format($hargaMin, 0, ',', '.') }} - Rp{{ number_format($hargaMax, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-white p-3 card-summary">
            <div class="d-flex align-items-center">
                <i class="bi bi-house fs-2 text-success me-3"></i>
                <div>
                    <p class="mb-0 text-muted">Jumlah Kos</p>
                    <h5>{{ $jumlahKos }} unit</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-white p-3 card-summary">
            <div class="d-flex align-items-center">
                <i class="bi bi-geo-alt fs-2 text-danger me-3"></i>
                <div>
                    <p class="mb-0 text-muted">Jarak Dari Kampus</p>
                    <h5>300 m - 15 km</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik: Harga Kos -->
<div class="card mt-5 p-4">
    <h5>Visualisasi Info Tempat Kos</h5>
    <canvas id="hargaKosChart" height="100"></canvas>
</div>

<!-- Grafik: Jenis Kos -->
<div class="card mt-5 p-4">
    <h5>Visualisasi Jumlah Tempat Kos per Jenis</h5>
    <canvas id="jenisKosChart" height="100"></canvas>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart Harga Kos
    const ctx = document.getElementById('hargaKosChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['≤ 450.000', '451.000 - 650.000', '651.000 - 1.000.000'],
            datasets: [{
                label: 'Jumlah',
                data: @json($chartData),
                backgroundColor: '#349eff',
                borderRadius: 10
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Chart Jenis Kos
    const jenisKosChart = document.getElementById('jenisKosChart');
    new Chart(jenisKosChart, {
        type: 'line',
        data: {
            labels: ['Putra', 'Putri', 'Campur'],
            datasets: [{
                label: 'Jumlah Kos',
                data: @json(array_values($chartJenisKos)),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4, // untuk efek melengkung
                pointBackgroundColor: '#4e73df'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
@endpush
