<!-- resources/views/penggajian/modals/modal_payroll_bank.blade.php -->
<div class="modal fade" id="modalPayrollBank" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Export Payroll Bank</h5>
            </div>

            {{-- gunakan GET supaya bisa dibuka di tab baru saat debugging; target _blank opsional --}}
            <form id="formExportBank" action="{{ route('payroll.export.bank') }}" method="POST" autocomplete="off">
                @csrf

                <input type="hidden" name="bulan"
                    value="{{ $selYear }}-{{ str_pad($selMonth, 2, '0', STR_PAD_LEFT) }}">

                <div class="modal-body">

                    <!-- Tanggal Payroll -->
                    <div class="mb-3">
                        <label class="form-label">Tanggal Payroll</label>
                        <input type="date" name="payroll_date" class="form-control">
                    </div>

                    <!-- FILTER DEPARTEMEN — VALUE = NID (sesuai muser.niddept) -->
                    <div class="mb-3">
                        <label class="form-label">Filter Departemen</label>
                        <select name="department_id" id="departmentSelect" class="form-select">
                            <option value="">-- Semua Departemen --</option>
                            @foreach ($departments as $dep)
                                {{-- pastikan menggunakan nid (primary key departemen pada DB Anda jika beda, ganti sesuai) --}}
                                <option value="{{ $dep->nid }}">{{ $dep->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- PILIH FORMAT BANK -->
                    <div class="mb-3">
                        <label class="form-label">Format Bank</label>
                        <select name="bank" id="bankSelect" class="form-select" required>
                            <option value="">-- Pilih Format --</option>
                            <option value="bca">BCA</option>
                            <option value="mandiri">Mandiri</option>
                            <option value="bri">BRI</option>
                        </select>
                    </div>

                    <!-- REKENING MANDIRI (hanya tampil saat Mandiri dipilih) -->
                    <div class="mb-3" id="rekeningMandiriBox" style="display:none;">
                        <label class="form-label">Pilih Rekening Sumber (Mandiri)</label>
                        <select name="mrekening_id" id="mrekeningSelect" class="form-select">
                            <option value="">-- Pilih Rekening --</option>
                            @foreach ($mrekening as $rek)
                                {{-- dataset bank untuk memfilter options jika diperlukan --}}
                                <option value="{{ $rek->id }}" data-bank="{{ strtolower($rek->bank ?? '') }}">
                                    {{ $rek->bank }} - {{ $rek->atas_nama }} - {{ $rek->nomor_rekening }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- OPTIONAL: hidden selected_ids (tidak wajib dipakai; controller menangani) -->
                    <input type="hidden" name="selected_ids" id="selected_ids" value="">

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        console.log("🔥 Payroll Bank script loaded");

        const bankSelect = document.getElementById('bankSelect');
        const rekeningBox = document.getElementById('rekeningMandiriBox');
        const mrekeningSelect = document.getElementById('mrekeningSelect');
        const form = document.getElementById('formExportBank');

        if (!form) return;

        // ===============================
        // DEBUG HELPER (ANTI REFRESH)
        // ===============================
        function saveDebug(data) {
            sessionStorage.setItem("payroll_debug", JSON.stringify(data));
        }

        // tampilkan debug setelah reload
        const lastDebug = sessionStorage.getItem("payroll_debug");
        if (lastDebug) {
            console.log("🧠 DEBUG SEBELUM REFRESH:");
            console.log(JSON.parse(lastDebug));
            sessionStorage.removeItem("payroll_debug");
        }

        // ===============================
        // TOGGLE MANDIRI
        // ===============================
        function toggleMandiriOptions() {
            const bank = bankSelect?.value || '';
            const isMandiri = bank.toLowerCase() === 'mandiri';

            if (rekeningBox)
                rekeningBox.style.display = isMandiri ? 'block' : 'none';

            if (mrekeningSelect) {
                Array.from(mrekeningSelect.options).forEach(o => {
                    const b = (o.dataset.bank || '').toLowerCase();
                    o.style.display = (!isMandiri || b.includes('mandiri')) ? '' : 'none';
                });

                if (!isMandiri) mrekeningSelect.value = '';
            }
        }

        toggleMandiriOptions();

        if (bankSelect) {
            bankSelect.addEventListener('change', toggleMandiriOptions);
        }

        // ===============================
        // SUBMIT DEBUG (ANTI REFRESH)
        // ===============================
        let submitCount = 0;

        form.addEventListener('submit', function(ev) {

            submitCount++;

            const debugData = {
                time: new Date().toISOString(),
                submitCount: submitCount,
                bank: bankSelect?.value || '',
                rekening: mrekeningSelect?.value || '',
                payroll_date: document.querySelector('[name=payroll_date]')?.value || '',
                selected_ids: document.getElementById('selected_ids')?.value || '',
            };

            console.log("🚀 SUBMIT:", debugData);

            // simpan sebelum reload
            saveDebug(debugData);

            // VALIDASI
            const bank = bankSelect?.value?.trim() || '';
            const rekening = mrekeningSelect?.value || '';

            if (!bank) {
                ev.preventDefault();
                alert('Pilih format bank terlebih dahulu.');
                return;
            }

            if (bank.toLowerCase() === 'mandiri' && !rekening) {
                ev.preventDefault();
                alert('Silakan pilih Rekening Sumber untuk Mandiri.');
                return;
            }

            console.log("✅ VALIDATION PASSED");
        });

    });
</script>
