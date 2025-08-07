@extends('layouts.sidebar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 px-2">
    <h5 class="mb-0">Selamat datang, {{ Auth::user()->name }}</h5>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-primary btn-sm d-flex align-items-center">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </button>
    </form>
</div>
<div class="container">
    <h1 class="judul">Data Fasilitas Kos</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <button type="button" class="btn btn-primary my-3" data-bs-toggle="modal" data-bs-target="#modalTambahFasilitas">
        Tambah Fasilitas
    </button>
    {{-- Include Modal Tambah --}}
@include('admin.fasilitas.create')

    <table class="table table-bordered">
        <thead class="text-center">
            <tr>
                <th>No</th>
                <th>Nama Fasilitas</th>
                <th>Kategori</th>
                <th>Index</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataFasilitas as $i => $fasilitas)
                <tr>
                    <td class="text-center">{{ $i + $dataFasilitas->firstItem() }}</td>
                    <td>{{ $fasilitas->nama_fasilitas }}</td>
                    <td>{{ $fasilitas->kategori?->nama_kategori ?? '-' }}</td>
                    <td class="text-center">{{ $fasilitas->index }}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditFasilitas{{ $fasilitas->id }}">Edit</button>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapusFasilitas{{ $fasilitas->id }}">Hapus</button>
                    </td>
                </tr>

                {{-- Include Modal Edit --}}
                @include('admin.fasilitas.edit', ['fasilitas' => $fasilitas])
                {{-- Include Modal Hapus --}}
                @include('admin.fasilitas.delete', ['fasilitas' => $fasilitas])
            @endforeach
        </tbody>
    </table>

    {{ $dataFasilitas->links() }}
</div>
@endsection
