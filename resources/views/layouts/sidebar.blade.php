<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
     <!-- Logo tittle -->
    <link rel="icon" href="{{ asset('assets/img/logo.png') }}" type="image/x-icon">
    <title>Admin | RumahKos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- My Style -->
     <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar" style="background-color: #71B4F3;">
            <div class="p-4 d-flex align-items-center">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Logo RumahKost" style="width: 30px; height: 30px;">
                    <span style="font-size: 18px; font-weight: bold; line-height: 1; padding: 0; margin: 0;">RumahKost</span>
                </div>
            </div>

        <a href="{{ route('admin.dashboard') }}"
            class="d-flex align-items-center {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i> Beranda
        </a>
        <a href="{{ route('kos.index') }}" 
            class="d-flex align-items-center {{ request()->routeIs('kos.*') ? 'active' : '' }}">
            <i class="bi bi-building me-2"></i> Data Kos
        </a>
        <a href="{{ route('fasilitas.index') }}" class="d-flex align-items-center">
            <i class="bi bi-sliders2-vertical me-2"></i> Data Fasilitas
        </a>
        <a href="#" class="d-flex align-items-center">
            <i class="bi bi-stars me-2"></i> Sistem Rekomendasi
        </a>

        </div>

        <!-- Main Content -->
        <div class="content w-100">
            @yield('content')
        </div>
    </div>
    <!-- Bootstrap JS + Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
