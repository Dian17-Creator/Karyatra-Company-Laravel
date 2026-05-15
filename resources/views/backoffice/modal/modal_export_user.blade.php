{{-- ===== MODAL EXPORT USER ===== --}}
<div class="modal fade" id="exportUserModal" tabindex="-1" aria-labelledby="exportUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="exportUserModalLabel">
                    Export Daftar User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-3">
                    Pilih format export yang diinginkan
                </p>

                <div class="d-grid gap-3">
                    {{-- EXPORT EXCEL --}}
                    <a href="{{ route('backoffice.users.export.excel') }}" class="btn btn-success">
                        📊 Export ke Excel
                        <div class="small text-white-50">
                            Data tabel (tanpa foto / link foto)
                        </div>
                    </a>

                    {{-- EXPORT PDF --}}
                    <a href="{{ route('backoffice.users.export.pdf') }}" class="btn btn-danger">
                        📄 Export ke PDF
                        <div class="small text-white-50">
                            Lengkap dengan foto wajah
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
