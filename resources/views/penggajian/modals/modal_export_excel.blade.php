<!-- resources/views/penggajian/modal_export_excel.blade.php -->
<div class="modal fade" id="modalExportExcel" tabindex="-1" aria-labelledby="modalExportExcelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formExportExcel" method="POST" action="{{ route('gaji.exportByDepartment') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExportExcelLabel">Export Payroll — Filter Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Departemen</label>
                        <select name="department" id="department" class="form-select">
                            <option value="">— Pilih Department —</option>
                            @foreach ($departments ?? [] as $dept)
                                <option value="{{ $dept->nid }}">{{ $dept->cname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Export & Download</button>
                </div>
            </div>
        </form>
    </div>
</div>
