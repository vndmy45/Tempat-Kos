@extends('layouts.sidebar')

@section('content')
    <h1 class="judul">Data Kos</h1>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row mb-3 justify-content-end mb-3">
        <div class="col-auto">
            <!-- Tombol Tambah Kos -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKos">
                Tambah Kos
            </button>
        </div>
        <div class="col-auto">
            <!-- Form Pencarian -->
            <form method="GET" action="{{ route('kos.index') }}" class="d-flex justify-content-end">
                <input type="text" name="search" class="form-control me-2" placeholder="Cari kos..."
                value="{{ request('search') }}" style="width: 200px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            </form>
        </div>
    </div>

<!-- Modal Create Kos -->
@include('admin.kos.create')

    {{-- Tampilkan Data Kos --}}


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama Kos</th>
                <th>Alamat</th>
                <th>Harga</th>
                <th>Fasilitas</th>
                <th>Jenis</th>
                <th>Rating</th>
                <th>Kontak</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dataKos as $kos)
                <tr>
                    <td>{{ $kos->nama_kos }}</td>
                    <td>{{ $kos->alamat }}</td>
                    <td>{{ $kos->harga }}</td>
                    <td>{{ $kos->fasilitas->pluck('nama_fasilitas')->join(', ') ?: '-' }}</td>
                    <td>{{ $kos->jenis_kost }}</td>
                    <td>{{ $kos->nilai_rating ?? '-' }}</td>
                    <td>{{ $kos->kontak_pemilik }}</td>
                    <td>
                        @if ($kos->gambarKos->first())
                            <img src="{{ $kos->gambarKos->first()->link_foto }}" alt="Foto Kos" width="80" height="60">
                        @else
                            <span class="text-muted">Belum ada foto</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('kos.show', $kos->id) }}" class="btn btn-info btn-sm">Detail</a>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditKos{{ $kos->id }}">Edit</button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapusKos{{ $kos->id }}">Hapus</button>
                    </td>
                </tr>
                <!-- Include modal khusus untuk $kos -->
            @include('admin.kos.edit', ['kos' => $kos])
            @include('admin.kos.delete', ['kos' => $kos])
            @endforeach

            @if($dataKos->isEmpty())
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data kos.</td>
                </tr>
            @endif
        </tbody>
    </table>
    {{-- Pagination --}}
    {{ $dataKos->links() }}
@endsection
