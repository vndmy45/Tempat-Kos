@extends('layouts.app')
@section('content')
<!-- Hero Section -->
       <section id="hero" class="d-flex align-items-center position-relative">
            <div class="container h-100">
                <div class="row h-100">
                    <div class="col-md-6 hero-tagline my-auto">
                        <h1>Selamat Datang Di Sistem Rekomendasi Tempat Kos</h1>
                        <p> <span class="fw-bold">Rumah Kos</span> hadir untuk temukan tempat kos terbaik
                            untukmu, untuk disewa dan sebagai tempat ternyaman di sekitar kampus poliwangi.</p>

                            <a href="{{ route('pencarian.index') }}">
                                <button class="button-lg-primary">Temukan Kos</button>
                                <img src="{{ asset('assets/img/arrow.png') }}" alt="">
                            </a>
                    </div>
                    </div>
                </div>
                
                <img src={{asset("assets/img/Banner.png")}} alt="" class="position-absolute end-0 top-0
                img-hero">
                <img src={{asset("assets/img/aksen.png")}} alt="" class=" accsent-img h-100 position-absolute top-0 start-0">
            </div>
       </section>

    <!-- Layanan Section -->
     <section id="layanan">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                        <h2>Layanan Kami</h2>
                        <span class="sub-tittle">Rumah Kos hadir menjadi solusi bagi kamu</span>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-md-4 text-center">
                    <div class="card-layanan">
                        <div class="circle-icon position-relative mx-auto">
                        <img src="{{ asset('assets/img/house 1.png') }}" alt="" class="position-absolute top-50 start-50 translate-middle">
                        </div>
                        <h3 class="mt-4">Fasilitas Lengkap</h3>
                        <p class="mt-3">Kost nyaman dengan fasilitas yang lengkap, sesuai dengan preferensi pengguna</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="card-layanan">
                        <div class="circle-icon position-relative mx-auto">
                            <img src= "{{ asset('assets/img/assets 1.png') }}" alt="" class="position-absolute top-50 start-50 translate-middle">
                        </div>
                        <h3 class="mt-4">Harga Mahasiswa</h3>
                        <p class="mt-3">Temukan pilihan terbaik dengan harga terjangkau! Kualitas tetap terjaga, dompet tetap aman</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="card-layanan">
                        <div class="circle-icon position-relative mx-auto">
                            <img src="{{ asset("assets/img/town 1.png") }}" alt="" class="position-absolute top-50 start-50 translate-middle">
                        </div>
                        <h3 class="mt-4">Jarak Dengan Kampus</h3>
                        <p class="mt-3">Hanya beberapa menit dari kampus, akses mudah dan cepat</p>
                    </div>
                </div>
            </div>

        </div>

     </section>

    <!-- Search Section -->
     <section id="Search" class="d-flex align-items-center position-relative">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h2>
                        Temukan Kos Impianmu
                    </h2>
                    <p>
                        sekarang Kamu dapat menghemat semua hal seperti waktu, biaya dan tenaga! jadi tenang buat ke kampus
                    </p>

                    <img src="{{ asset("assets/img/bg2.png") }}" alt="" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: -1;">
                </div>
     </section>
    
     <!-- Rekomendasi Section -->
    <section id="rekomendasi" class="py-5">
    <div class="container"> 
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2>Rekomendasi Kos Untukmu</h2>
            </div>
        </div>

        <div class="row g-4">
            @forelse($rekomendasiKos as $kos)
                <div class="col-md-3">
                    <div class="card kost-card">
                        <img src="{{ $kos->gambarKos->first() ? asset($kos->gambarKos->first()->link_foto) : asset('default.jpg') }}" class="card-img-top" alt="{{ $kos->nama }}">
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
                            @auth
                                <a href="{{ route('user.kos.show', ['id' => $kos->id, 'from' => request()->fullUrl()]) }}" class="btn btn-primary w-100">Detail</a>
                            @else
                                <a href="{{ route('login', ['redirect' => route('user.kos.show', ['id' => $kos->id, 'from' => request()->fullUrl()])]) }}" class="btn btn-primary w-100">Detail</a>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">Tidak ada kos rekomendasi saat ini.</div>
                </div>
            @endforelse
        </div>
    </div>
    </section>

       <!-- Kontak -->
    <section id="kontak">
        <div class="container-fluid overlay d-flex align-items-center" style="min-height: 100vh;">
        <div class="container">
            <div class="row">
                <div class="col-md-6 d-flex align-items-center justify-content-center" style="min-height: 100vh;">
                    <div class="text-center">
                        <h3 class="text-white">
                            Tunggu Apa Lagi? <br>
                            Temukan Kost Impianmu Bersama <span style="color:#71B4F3;">Rumah Kost</span>
                        </h3>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-center" style="min-height: 100vh;"">
                    <div class="kontak text-white">
                        <h6>Kontak</h6>
                        <div class="mb-3 d-flex align-items-center">
                            <img src="{{ asset('assets/img/Maps.png') }}" class="me-2">
                            <a href="#">Jl. Pelajar Pejuang 123 Majalaya Bandung Indonesia</a>
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <img src="{{ asset('assets/img/Telepon.png') }}" class="me-2">
                            <a href="#">022-6545-2041</a>
                        </div>
                        <div class="mb-3 d-flex align-items-center">
                            <img src="{{ asset('assets/img/Gmail.png') }}" class="me-2">
                            <a href="#">RumahKost@gmail.com</a>
                        </div>

                        <h6 class="mt-3">Social Media</h6>
                        <div>
                            <a href="#"><img src="{{ asset('assets/img/facebook.png') }}" class="me-2"></a>
                            <a href="#"><img src="{{ asset('assets/img/tweeter.png') }}" class="me-2"></a>
                            <a href="#"><img src="{{ asset('assets/img/instagram (1).png') }}" class="me-2"></a>
                            <a href="#" class="linkrumahimpian">RumahKost</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection