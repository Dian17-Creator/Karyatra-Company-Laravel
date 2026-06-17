<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('backoffice.addDepartment') }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Tambah Departemen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Departemen</label>
                        <input type="text" name="cname" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between w-100 gap-2">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success flex-fill">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>