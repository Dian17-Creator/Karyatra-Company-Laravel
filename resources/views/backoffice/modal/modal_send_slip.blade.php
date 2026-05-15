{{-- resources/views/backoffice/modal_send_slip.blade.php --}}
<div class="modal fade" id="sendSlipModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('backoffice.importSlips') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Kirim Slip Gaji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <strong>Pilih File Slip Gaji (format .xlsx)</strong>
                        <p class="text-muted small">
                            Gunakan template
                            <a href="{{ asset('templates/template_slip_gaji.xlsx') }}" download
                                class="fw-bold text-primary text-decoration-none">
                                Download template
                            </a>
                        </p>
                    </div>

                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import & Kirim</button>
                </div>
            </div>
        </form>
    </div>
</div>
