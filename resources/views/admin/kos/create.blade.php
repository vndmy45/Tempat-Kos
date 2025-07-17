<!-- Modal Tambah Kos -->
<div class="modal fade" id="modalTambahKos" tabindex="-1" aria-labelledby="modalTambahKosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('kos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKosLabel">Tambah Data Kos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    {{-- Form Input --}}
                    <div class="mb-3">
                        <label for="nama_kos" class="form-label">Nama Kos</label>
                        <input type="text" class="form-control" name="nama_kos" value="{{ old('nama_kos') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="2" required>{{ old('alamat') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="harga" class="form-label">Harga</label>
                        <input type="number" class="form-control" name="harga" value="{{ old('harga') }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fasilitas" class="form-label">Fasilitas</label>
                        <div class="row">
                            @foreach ($dataFasilitas as $fasilitas)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fasilitas[]" value="{{ $fasilitas->id }}"
                                            id="fasilitas_{{ $fasilitas->id }}"
                                            {{ isset($kos) && $kos->fasilitas->contains($fasilitas->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fasilitas_{{ $fasilitas->id }}">
                                            {{ $fasilitas->nama_fasilitas }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="jenis_kost" class="form-label">Jenis Kost</label>
                        <select class="form-select" name="jenis_kost" required>
                            <option disabled selected>Pilih jenis kos</option>
                            <option value="Putra" {{ old('jenis_kost') == 'Putra' ? 'selected' : '' }}>Putra</option>
                            <option value="Putri" {{ old('jenis_kost') == 'Putri' ? 'selected' : '' }}>Putri</option>
                            <option value="Campur" {{ old('jenis_kost') == 'Campur' ? 'selected' : '' }}>Campur</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" name="longitude" value="{{ old('longitude') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" name="latitude" value="{{ old('latitude') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nilai_rating" class="form-label">Rating</label>
                        <input type="number" step="0.1" class="form-control" name="nilai_rating" value="{{ old('nilai_rating') }}">
                    </div>

                    <div class="mb-3">
                        <label for="kontak_pemilik" class="form-label">Kontak Pemilik</label>
                        <input type="text" class="form-control" name="kontak_pemilik" value="{{ old('kontak_pemilik') }}" required>
                    </div>
                    <!-- Upload Gambar -->
                    <div class="mb-3">
                        <label for="gambar" class="form-label">Gambar Kos</label>
                        <input type="file" name="gambar[]" class="form-control" multiple required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
