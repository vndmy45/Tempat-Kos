@extends('layouts.guest')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
    {{-- Card Login Full Biru --}}
    <div class="shadow rounded d-flex overflow-hidden" style="width: 750px; background-color: #65B3F2;">
        
        {{-- Kiri: Icon --}}
        <div class="d-flex flex-column justify-content-center align-items-center p-4" style="width: 40%;">
            <i class="bi bi-person-circle" style="font-size: 100px; color: white;"></i>
            <h4 class="mt-3 text-white">LOGIN</h4>
        </div>

        {{-- Kanan: Form login (juga biru) --}}
        <div class="p-4 text-white" style="width: 60%;">
            <h4 class="mb-4 fw-bold">Halo, Selamat Datang</h4>

            {{-- Alert Sukses --}}
            @if(session('status'))
                <div class="alert alert-success text-dark bg-white">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Alert Validasi --}}
            @if($errors->any())
                <div class="alert alert-danger bg-white text-dark">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label text-white">Email / Username</label>
                    <input type="text" class="form-control rounded-pill" id="email" name="email"
                        value="{{ old('email') }}" required placeholder="Masukkan Username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label text-white">Kata Sandi</label>
                    <input type="password" class="form-control rounded-pill" id="password" name="password"
                        required placeholder="Masukkan Kata Sandi">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label text-white" for="remember">
                        Ingat saya
                    </label>
                </div>

                <div class="d-grid mb-2">
                    <button type="submit" class="btn btn-light text-primary rounded-pill fw-bold hover-effect">
                        Masuk
                    </button>
                </div>

                <div class="text-center mb-2">
                    <span class="text-white">atau</span>
                </div>

                <div class="d-grid">
                <a href="{{ route('register') }}" class="btn btn-outline-light rounded-pill fw-bold text-white border-white hover-register">
                    Register
                </a>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="text-white">Lupa Kata Sandi?</a>
                </div>
            </div>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection
