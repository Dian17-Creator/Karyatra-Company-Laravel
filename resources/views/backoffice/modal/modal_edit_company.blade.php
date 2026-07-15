@if ($company)
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('backoffice.updateCompany') }}">
                @csrf
                @method('PUT')
                <div class="bg-danger modal-header text-white">
                    <h5 class="modal-title" id="editCompanyModalLabel">
                        <i class="bi bi-building me-1"></i> Edit Data Company
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Company</label>
                        <input type="text" name="cname" class="form-control"
                            value="{{ old('cname', $company->cname) }}" required maxlength="255"
                            placeholder="Contoh: PT Matahati Indonesia">
                        <div class="form-text">Perubahan nama company akan sinkronisasi ke semua data user terkait.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Domain Email Company</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" name="cemail" class="form-control"
                                value="{{ old('cemail', $company->cemail) }}" required maxlength="255"
                                placeholder="Contoh: matahati">
                        </div>
                        <div class="form-text">
                            Domain ini dipakai untuk format login karyawan: <strong>username@domain</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between w-100 gap-2">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success text-white flex-fill">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif