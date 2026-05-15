<div class="modal fade" id="deleteDepartmentModal{{ $dept->nid }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('backoffice.deleteDepartment') }}">
            @csrf
            <input type="hidden" name="dept_id" value="{{ $dept->nid }}">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Hapus Departemen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Apakah kamu yakin ingin menghapus departemen
                    <b>{{ $dept->cname }}</b>?
                    <br>
                    <small class="text-muted">
                        (Data tidak bisa dikembalikan setelah dihapus)
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
