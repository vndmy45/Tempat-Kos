<!-- Modal Edit Kos -->
<div class="modal fade" id="modalEditKos{{ $kos->id }}" tabindex="-1" aria-labelledby="modalEditKosLabel{{ $kos->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @if(isset($kos))
            <form action="{{ route('kos.update', $kos->id) }}" method="POST">
                @csrf 
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditKosLabel{{ $kos->id }}">Edit Data Kos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    {{-- Form Input --}}
                    <div class="mb-3">
                        <label for="nama_kos" class="form-label">Nama Kos</label>
                        <input type="text" class="form-control @error('nama_kos') is-invalid @enderror" name="nama_kos" value="{{ old('nama_kos', $kos->nama_kos) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control @error('alamat') is-invalid @enderror" name="alamat" required>{{ old('alamat', $kos->alamat) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="harga" class="form-label">Harga</label>
                        <input type="number" class="form-control @error('harga') is-invalid @enderror" name="harga" value="{{ old('harga', $kos->harga) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="jenis_kost" class="form-label">Jenis Kost</label>
                        <select class="form-select @error('jenis_kost') is-invalid @enderror" name="jenis_kost" required>
                            <option disabled selected>Pilih jenis kos</option>
                            <option value="Putra" {{ old('jenis_kost', $kos->jenis_kost) == 'Putra' ? 'selected' : '' }}>Putra</option>
                            <option value="Putri" {{ old('jenis_kost', $kos->jenis_kost) == 'Putri' ? 'selected' : '' }}>Putri</option>
                            <option value="Campur" {{ old('jenis_kost', $kos->jenis_kost) == 'Campur' ? 'selected' : '' }}>Campur</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control @error('longitude') is-invalid @enderror" name="longitude" value="{{ old('longitude', $kos->longitude) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control @error('latitude') is-invalid @enderror" name="latitude" value="{{ old('latitude', $kos->latitude) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nilai_rating" class="form-label">Rating</label>
                        <input type="number" step="0.1" class="form-control @error('nilai_rating') is-invalid @enderror" name="nilai_rating" value="{{ old('nilai_rating', $kos->nilai_rating) }}">
                    </div>

                    <div class="mb-3">
                        <label for="kontak_pemilik" class="form-label">Kontak Pemilik</label>
                        <input type="text" class="form-control @error('kontak_pemilik') is-invalid @enderror" name="kontak_pemilik" value="{{ old('kontak_pemilik', $kos->kontak_pemilik) }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
