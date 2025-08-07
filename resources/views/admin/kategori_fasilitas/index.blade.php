@extends('layouts.sidebar')
@section('content')

<h1>Kategori Fasilitas</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<!-- Tombol untuk menampilkan modal tambah -->
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
    <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
</button>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Kategori</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($kategori as $i => $item)
        <tr>
            <td>{{ $i + $kategori->firstItem() }}</td>
            <td>{{ $item->nama_kategori }}</td>
            <td>
                <!-- Tombol Edit -->
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditKategori{{ $item->id }}">
                    <i class="bi bi-pencil"></i>
                </button>

                <!-- Tombol Delete -->
                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalHapusKategori{{ $item->id }}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>

        <!-- Modal Edit -->
        <div class="modal fade" id="modalEditKategori{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form action="{{ route('kategori-fasilitas.update', $item->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label>Nama Kategori</label>
                                <input type="text" name="nama_kategori" value="{{ $item->nama_kategori }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-warning">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Hapus -->
        <div class="modal fade" id="modalHapusKategori{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form action="{{ route('kategori-fasilitas.destroy', $item->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title">Hapus Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Yakin ingin menghapus <strong>{{ $item->nama_kategori }}</strong>?
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-danger">Hapus</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @endforeach
    </tbody>
</table>

{{ $kategori->links() }}

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('kategori-fasilitas.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
