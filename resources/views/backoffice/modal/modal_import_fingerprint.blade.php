{{-- ====== MODAL IMPORT ABSENSI DARI FINGERPRINT ====== --}}
<div class="modal fade" id="importFingerprintModal" tabindex="-1" aria-labelledby="importFingerprintModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('attendance.importFingerprint') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="importFingerprintModalLabel">
                        Import Absensi dari File Fingerprint
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Fingerprint (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>

                    <ul class="small text-muted mb-0">
                        <li>Gunakan file export langsung dari mesin fingerprint (tanpa diedit).</li>
                        <li>Sistem akan otomatis membaca <b>sheet ke-4 (Exception Stat.)</b>.</li>
                        <li>Data yang sudah ada di database <b>tidak akan diubah</b>, hanya tanggal yang belum ada yang
                            akan diisi.</li>
                        <li>Jika bulan tersebut belum ada data sama sekali, semua data dari file akan dimasukkan.</li>
                    </ul>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary">
                        Import & Lengkapi Data
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
