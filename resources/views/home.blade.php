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

                            <a href="#">
                                <button class="button-lg-primary">Temukan Kos</button>
                                <img src={{"assets/img/arrow.png"}} alt="">
                            </a>
                    </div>
                </div>
                
                <img src={{"assets/img/Banner.png"}} alt="" class="position-absolute end-0 top-0
                img-hero">
                <img src={{"assets/img/aksen.png"}} alt="" class=" accsent-img h-100 position-absolute top-0 start-0">
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
      <section id="rekomendasi">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2>Rekomendasi Kos Untuk Mu</h2>

                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 d-flex justify-content-evenly">
                    <div class="card p-2" style="width: 22rem">
                        <img src="{{ asset("assets/img/Rumah1.png") }}" alt="">
                        <div class="card-body">
                            <h4>IDR.200.000.000</h4>
                            <p class="mb-4 lh-sm">Jl. Soekarno Hatta No.1 <br> <span class="text-danger">Sewa</span></p>
                        </div>
                        <div class="card-fasilitas d-flex justify-content-between px-4">
                            <span>
                                <img src="{{ asset("assets/img/Kasur.png") }}" alt=""> 3
                                <p>Kamar Tidur</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/Mandi.png") }}" alt=""> 4
                                <p>Kamar Mandi</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/LuasRumah.png") }}" alt=""> 360
                                <p>Luas Rumah</p>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex justify-content-evenly">
                    <div class="card p-2" style="width: 22rem;">
                        <img src="{{ asset("assets/img/house2.png") }}" alt="">
                        <div class="card-body">
                            <h4>IDR.200.000.000</h4>
                            <p class="mb-4 lh-sm">Jl. Soekarno Hatta No.1 <br> <span class="text-danger">Sewa</span></p>
                        </div>
                        <div class="card-fasilitas d-flex justify-content-between px-4">
                            <span>
                                <img src="{{ asset("assets/img/Kasur.png") }}" alt=""> 3
                                <p>Kamar Tidur</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/Mandi.png") }}" alt=""> 4
                                <p>Kamar Mandi</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/LuasRumah.png") }}" alt=""> 360
                                <p>Luas Rumah</p>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex justify-content-evenly">
                    <div class="card p-2" style="width: 22rem;">
                        <img src="{{ asset("assets/img/house3.png") }}" alt="">
                        <div class="card-body">
                            <h4>IDR.200.000.000</h4>
                            <p class="mb-4 lh-sm">Jl. Soekarno Hatta No.1 <br> <span class="text-danger">Sewa</span></p>
                        </div>
                        <div class="card-fasilitas d-flex justify-content-between px-4">
                            <span>
                                <img src="{{ asset("assets/img/Kasur.png") }}" alt=""> 3
                                <p>Kamar Tidur</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/Mandi.png") }}" alt=""> 4
                                <p>Kamar Mandi</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/LuasRumah.png") }}" alt=""> 360
                                <p>Luas Rumah</p>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex justify-content-evenly">
                    <div class="card p-2" style="width: 22rem;">
                        <img src="{{ asset("assets/img/house4.png") }}" alt="">
                        <div class="card-body">
                            <h4>IDR.200.000.000</h4>
                            <p class="mb-4 lh-sm">Jl. Soekarno Hatta No.1 <br> <span class="text-danger">Sewa</span></p>
                        </div>
                        <div class="card-fasilitas d-flex justify-content-between px-4">
                            <span>
                                <img src="{{ asset("assets/img/Kasur.png") }}" alt=""> 3
                                <p>Kamar Tidur</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/Mandi.png") }}" alt=""> 4
                                <p>Kamar Mandi</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/LuasRumah.png") }}" alt=""> 360
                                <p>Luas Rumah</p>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex justify-content-evenly">
                    <div class="card p-2" style="width: 22rem;">
                        <img src="{{ asset("assets/img/house5.png") }}" alt="">
                        <div class="card-body">
                            <h4>IDR.200.000.000</h4>
                            <p class="mb-4 lh-sm">Jl. Soekarno Hatta No.1 <br> <span class="text-danger">Sewa</span></p>
                        </div>
                        <div class="card-fasilitas d-flex justify-content-between px-4">
                            <span>
                                <img src="{{ asset("assets/img/Kasur.png") }}" alt=""> 3
                                <p>Kamar Tidur</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/Mandi.png") }}" alt=""> 4
                                <p>Kamar Mandi</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/LuasRumah.png") }}" alt=""> 360
                                <p>Luas Rumah</p>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 d-flex justify-content-evenly">
                    <div class="card p-2" style="width: 22rem;">
                        <img src="{{ asset("assets/img/house6.png") }}" alt="">
                        <div class="card-body">
                            <h4>IDR.200.000.000</h4>
                            <p class="mb-4 lh-sm">Jl. Soekarno Hatta No.1 <br> <span class="text-danger">Sewa</span></p>
                        </div>
                        <div class="card-fasilitas d-flex justify-content-between px-4">
                            <span>
                                <img src="{{ asset("assets/img/Kasur.png") }}" alt=""> 3
                                <p>Kamar Tidur</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/Mandi.png") }}" alt=""> 4
                                <p>Kamar Mandi</p>
                            </span>
                            <span>
                                <img src="{{ asset("assets/img/LuasRumah.png") }}" alt=""> 360
                                <p>Luas Rumah</p>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </section>

      <!-- Fitur Section -->
       <section id="fitur" class="mt-5 overflow-hidden">
        <div class="container position-relative">
            <div class="row" mb-4>
                <div class="col-lg-9 col-md-12 text-center text-lg-start">
                    <h2>Fasilitas Kos</h2>
                </div>
                <div class="col-lg-3 col-md-12">
                    <button class="button-fitur">Lihat Semua..
                        <img src="{{ asset("assets/img/Vector.png") }}" alt="" class="ms-4">
                    </button>
                </div>
            </div>
            <div class="container position-relative mt-5">
                <div class="row">
                    <div class="col-12 d-flex justify-content-start">
                        <div class="card-fitur me-3 position-relative">
                            <img src="{{ asset("assets/img/Fitur1.png") }}" alt="">

                            <div class="overlay position-absolute top-0 buttom-0 start-0 end-0 w-100 h-100">
                                <div class="position-absolute top-50 start-50 translate-middle text-center w-100">
                                    <h5>Kamar Tidur</h5>
                                    <span>Rumah minimalis Type-A2</span>
                                    <h6>IDR.200jt</h6>
                                    <button>Lihat Rumah</button>
                                </div>
                            </div>
                        </div>

                        <div class="card-fitur me-3 position-relative">
                            <img src="{{ asset("assets/img/Fitur1.png") }}" alt="">

                            <div class="overlay position-absolute top-0 buttom-0 start-0 end-0 w-100 h-100">
                                <div class="position-absolute top-50 start-50 translate-middle text-center w-100">
                                    <h5>Kamar Tidur</h5>
                                    <span>Rumah minimalis Type-A2</span>
                                    <h6>IDR.200jt</h6>
                                    <button>Lihat Rumah</button>
                                </div>
                            </div>
                        </div>

                        <div class="card-fitur me-3 position-relative">
                            <img src="{{ asset("assets/img/Fitur1.png") }}" alt="">

                            <div class="overlay position-absolute top-0 buttom-0 start-0 end-0 w-100 h-100">
                                <div class="position-absolute top-50 start-50 translate-middle text-center w-100">
                                    <h5>Kamar Tidur</h5>
                                    <span>Rumah minimalis Type-A2</span>
                                    <h6>IDR.200jt</h6>
                                    <button>Lihat Rumah</button>
                                </div>
                            </div>
                        </div>

                        <div class="card-fitur me-3 position-relative">
                            <img src="{{ asset("assets/img/Fitur1.png") }}" alt="">

                            <div class="overlay position-absolute top-0 buttom-0 start-0 end-0 w-100 h-100">
                                <div class="position-absolute top-50 start-50 translate-middle text-center w-100">
                                    <h5>Kamar Tidur</h5>
                                    <span>Rumah minimalis Type-A2</span>
                                    <h6>IDR.200jt</h6>
                                    <button>Lihat Rumah</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="button-arrow-left position-absolute start-0 top-50 translate-middle-y">
                    <img src="{{ asset("assets/img/V-Kiri.png") }}" alt="">
                </button>
                <button class="button-arrow-right position-absolute end-0 top-50 translate-middle-y">
                    <img src="{{ asset("assets/img/V-Kanan.png") }}" alt="">
                </button>
            </div>
        </div>
       </section>

       <!-- Kontak -->
       <section id="kontak">
            <div class="container-fluid overlay h-100">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <h3>
                                Tunggu Apa Lagi? <br>
                                Temukan Kost Impianmu Bersama <span style="color:#71B4F3;">Rumah Kost</span>
                            </h3>
                            <div class="kontak">
                                <h6>Kontak</h6>
                                <div class="mb-3 d-flex align-items-center">
                                    <div>
                                        <img src="{{ asset("assets/img/Maps.png") }}" alt="">
                                    </div>
                                    <a href="#">Jl. Pelajar Pejuang 123 Majalaya Bandung 
                                        Indonesia</a>
                                </div>
                                <div class="mb-3 d-flex align-items-center">
                                    <div>
                                        <img src="{{ asset("assets/img/Telepon.png") }}" alt="">
                                    </div>
                                    <a href="#">022-6545-2041</a>
                                </div>
                                <div class="mb-3 d-flex align-items-center">
                                    <div>
                                        <img src="{{ asset("assets/img/Gmail.png") }}" alt="">
                                    </div>
                                    <a href="#">rumahimpian@gmail.com</a>
                                </div>
                            </div>
                            <h6>Social Media</h6>
                                <a href="#" class="me-lg-3 me-1"><img src="{{ asset("assets/img/facebook.png") }}" alt=""></a>
                                <a href="#" class="me-lg-3 me-1"><img src="{{ asset("assets/img/tweeter.png") }}" alt=""></a>
                                <a href="#" class="me-lg-3 me-1"><img src="{{ asset("assets/img/instagram (1).png") }}" alt=""></a>
                                <a href="#" class="linkrumahimpian">RumahKost</a>
                        </div>
                        <div class="col-md-6">
                            <div class="card-contact w-100">
                                <form> 
                                    <h2>
                                        Ada Pertanyaan?
                                    </h2>
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com">
                                        <label for="floatingInput " class=" d-flex align-items-center">Masukan email anda disini...</label>
                                      </div>
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="floatingInput" placeholder="name@example.com">
                                        <label for="floatingInput" class="d-flex align-items-center">Pertanyaan anda....</label>
                                    </div>
                                    <button type="submit" class="button-kontak">Kirim</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
       </section>
@endsection