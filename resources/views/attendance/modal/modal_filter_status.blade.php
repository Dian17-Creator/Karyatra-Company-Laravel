<!-- Modal: Filter Status Absen -->
<div class="modal fade" id="attStatusModal" tabindex="-1" aria-labelledby="attStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attStatusModalLabel">Filter Status Absen</h5>
            </div>
            <div class="modal-body">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="att_status" id="attStatusPresent"
                        value="present" checked>
                    <label class="form-check-label" for="attStatusPresent">Sudah Absen</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="att_status" id="attStatusMissing"
                        value="missing">
                    <label class="form-check-label" for="attStatusMissing">Belum Absen</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" id="applyAttStatusBtn" class="btn btn-success"
                    data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP = {
        attendanceUrl: "{{ route('attendance.report') }}",
        attendanceMissingUrl: "{{ route('attendance.missing') }}", // ← TAMBAH INI
        exportUrl: "{{ route('export-attendance-report') }}"
    };
</script>
