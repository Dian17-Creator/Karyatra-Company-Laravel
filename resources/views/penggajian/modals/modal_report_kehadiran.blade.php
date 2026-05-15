<!-- resources/views/penggajian/modals/modal_report_kehadiran.blade.php -->
<div class="modal fade" id="modalReportKehadiran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Export Report Kehadiran</h5>
            </div>

            <form id="formExportReportKehadiran" action="{{ route('kehadiran.export.report') }}" method="GET"
                target="_blank" autocomplete="off">

                <div class="modal-body">

                    <!-- BULAN -->
                    <div class="mb-3">
                        <label for="bulanKehadiran" class="form-label">Pilih Bulan</label>
                        <input id="bulanKehadiran" name="bulan" type="month" class="form-control"
                            value="{{ ($selYear ?? now()->year) . '-' . str_pad($selMonth ?? now()->month, 2, '0', STR_PAD_LEFT) }}">
                        <div class="form-text">Pilih periode laporan (tahun-bulan).</div>
                    </div>

                    <!-- DEPARTMENT -->
                    <div class="mb-3">
                        <label for="departmentKehadiran" class="form-label">Filter Departemen</label>
                        <select id="departmentKehadiran" name="department_id" class="form-select">
                            <option value="">-- Semua Departemen --</option>
                            @foreach ($departments as $dep)
                                <option value="{{ $dep->nid ?? $dep->id }}">
                                    {{ $dep->cname }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Kosongkan untuk semua departemen.</div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>
