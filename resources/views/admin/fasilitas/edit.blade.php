<!-- Modal Edit Fasilitas -->
<div class="modal fade" id="modalEditFasilitas{{ $fasilitas->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('fasilitas.update', $fasilitas->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fasilitas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Nama</label>
                        <input type="text" name="nama_fasilitas" class="form-control" value="{{ $fasilitas->nama_fasilitas }}" required>
                    </div>
                    <div class="mb-2">
                        <label>Index</label>
                        <input type="number" name="index" class="form-control" value="{{ $fasilitas->index }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
