<div class="modal fade" id="editDepartmentModal{{ $dept->nid }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('backoffice.updateDepartment', $dept->nid) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Departemen</label>
                        <input type="text" name="cname" class="form-control" value="{{ $dept->cname }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
