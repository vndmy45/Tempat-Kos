@extends('layouts.guest')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="shadow rounded d-flex overflow-hidden" style="width: 750px; background-color: #65B3F2;">
        
        {{-- Kiri: Icon --}}
        <div class="d-flex flex-column justify-content-center align-items-center p-4" style="width: 40%;">
            <i class="bi bi-person-circle" style="font-size: 100px; color: white;"></i>
            <h4 class="mt-3 text-white">REGISTER</h4>
        </div>

        {{-- Kanan: Form Register --}}
        <div class="p-4 text-white" style="width: 60%;">
            <h4 class="mb-4 fw-bold">Selamat Datang, Silakan Daftar</h4>

            @if($errors->any())
                <div class="alert alert-danger bg-white text-dark">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label text-white">Nama</label>
                    <input type="text" class="form-control rounded-pill" id="name" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label text-white">Email</label>
                    <input type="email" class="form-control rounded-pill" id="email" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label text-white">Password</label>
                    <input type="password" class="form-control rounded-pill" id="password" name="password" required>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label text-white">Konfirmasi Password</label>
                    <input type="password" class="form-control rounded-pill" id="password_confirmation" name="password_confirmation" required>
                </div>

                <div class="d-grid mb-2">
                    <button type="submit" class="btn btn-light text-primary rounded-pill fw-bold">
                        Daftar
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-white">Sudah punya akun?</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
