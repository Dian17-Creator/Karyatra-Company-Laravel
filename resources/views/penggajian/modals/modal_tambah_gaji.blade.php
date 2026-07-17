<!-- Modal Tambah Gaji -->

<style>
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }

    #nominal_gaji_fmt {
        text-align: right;
    }

    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .modal-body .form-label {
        margin-bottom: 4px;
        /* default Bootstrap ~8px */
        font-size: 0.85rem;
    }

    .modal-body .form-control,
    .modal-body .form-select {
        padding: 6px 10px;
        font-size: 0.9rem;
    }
</style>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="modal fade" id="modalTambahGaji" tabindex="-1" aria-labelledby="modalTambahGajiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <form action="{{ route('tunjangan.store') }}" method="POST" id="formTambahGaji">
                @csrf

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalTambahGajiLabel">Tambah Data Gaji & Tunjangan</h5>
                </div>

                <div class="modal-body">
                    <div class="row gx-3 gy-2">

                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold text-muted">Pilih Karyawan</label>
                            <select name="nid" id="nid" class="form-select" required>
                                <option value="">Pilih Karyawan</option>
                                @foreach ($users as $u)
                                <option value="{{ $u->nid }}">{{ $u->cname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold text-muted">Tanggal Berlaku</label>
                            <input type="date" name="tanggal_berlaku" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold text-muted">Jenis Gaji</label>
                            <select name="jenis_gaji" id="jenis_gaji" class="form-select" required>
                                <option value="pokok">Gaji Bulanan</option>
                                <option value="harian">Gaji Harian</option>
                            </select>
                        </div>

                        <!-- NOMINAL GAJI -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-muted">Nominal Gaji</label>
                            <input type="text" id="nominal_gaji_fmt" class="form-control text-end uang"
                                placeholder="0" autocomplete="off">
                            <input type="hidden" id="nominal_gaji" name="nominal_gaji" value="0">
                        </div>

                        {{-- ================= TUNJANGAN ================= --}}
                        <div class="d-flex align-items-center" style="margin-top: 20px">
                            <div class="flex-grow-1 border-top"></div>
                            <h6 class="text-success fw-bold px-3 mb-3">
                                TUNJANGAN
                            </h6>
                            <div class="flex-grow-1 border-top"></div>
                        </div>

                        <div class="row gx-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Tunjangan Makan</label>
                                <input type="text" id="tunjangan_makan_fmt" class="form-control text-end uang"
                                    placeholder="0" autocomplete="off">
                                <input type="hidden" id="tunjangan_makan" name="tunjangan_makan" value="0">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Tunjangan Jabatan</label>
                                <input type="text" id="tunjangan_jabatan_fmt" class="form-control text-end uang"
                                    placeholder="0" autocomplete="off">
                                <input type="hidden" id="tunjangan_jabatan" name="tunjangan_jabatan" value="0">
                            </div>
                        </div>

                        <div class="row gx-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Tunjangan Transport</label>
                                <input type="text" id="tunjangan_transport_fmt" class="form-control text-end uang"
                                    placeholder="0" autocomplete="off">
                                <input type="hidden" id="tunjangan_transport" name="tunjangan_transport"
                                    value="0">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Tunjangan Luar Kota</label>
                                <input type="text" id="tunjangan_luar_kota_fmt" class="form-control text-end uang"
                                    placeholder="0" autocomplete="off">
                                <input type="hidden" id="tunjangan_luar_kota" name="tunjangan_luar_kota"
                                    value="0">
                            </div>
                        </div>

                        <div class="row gx-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Tunjangan Masa Kerja</label>
                                <input type="text" id="tunjangan_masa_kerja_fmt"
                                    class="form-control text-end uang" placeholder="0" autocomplete="off">
                                <input type="hidden" id="tunjangan_masa_kerja" name="tunjangan_masa_kerja"
                                    value="0">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Tunjangan Backup</label>
                                <input type="text" id="tunjangan_backup_fmt" class="form-control text-end uang"
                                    placeholder="0" autocomplete="off">
                                <input type="hidden" id="tunjangan_backup" name="tunjangan_backup" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between w-100 gap-2">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success flex-fill" id="btnSubmit">Simpan</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ===============================
         * FORMAT INPUT UANG (CLEAN)
         * =============================== */
        document.querySelectorAll('.uang').forEach(input => {

            // 1️⃣ SAAT NGETIK → VALIDASI SAJA (TIDAK SENTUH HIDDEN)
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9,]/g, '');
            });

            // 2️⃣ SAAT BLUR → FORMAT RIBUAN
            input.addEventListener('blur', function() {
                if (!this.value) return;

                const parts = this.value.split(',');
                let integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                let decimal = parts[1] ?? '';

                this.value = decimal ? integer + ',' + decimal : integer;
            });

        });

        /* ===============================
         * ELEMENT REFERENCES
         * =============================== */
        const userSelect = document.getElementById('nid');
        const form = document.getElementById('formTambahGaji');
        const btnSubmit = document.getElementById('btnSubmit');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const latestUrlBase = "{{ url('penggajian/tunjangan/latest') }}";

        /* ===============================
         * APPLY DATA KE FORM (AMAN)
         * =============================== */
        function setUang(id, value) {
            const fmt = document.getElementById(id + '_fmt');
            const hid = document.getElementById(id);
            const raw = Number(value) || 0;

            if (hid) hid.value = raw;
            if (fmt) {
                fmt.value = raw ? raw.toLocaleString('id-ID') : '';
            }
        }

        function applyData(payload) {
            document.getElementById('jenis_gaji').value = payload.jenis_gaji ?? 'pokok';

            setUang('nominal_gaji', payload.nominal_gaji);
            setUang('tunjangan_makan', payload.t_makan);
            setUang('tunjangan_jabatan', payload.t_jabatan);
            setUang('tunjangan_transport', payload.t_transport);
            setUang('tunjangan_luar_kota', payload.t_luarkota);
            setUang('tunjangan_masa_kerja', payload.t_masakerja);
            setUang('tunjangan_backup', payload.t_backup);
        }

        function resetForm() {
            applyData({
                jenis_gaji: 'pokok',
                nominal_gaji: 0,
                t_makan: 0,
                t_jabatan: 0,
                t_transport: 0,
                t_luarkota: 0,
                t_masakerja: 0,
                t_backup: 0,
            });
        }

        /* ===============================
         * LOAD DATA TERAKHIR USER
         * =============================== */
        if (userSelect) {
            userSelect.addEventListener('change', function() {
                const nid = this.value;
                if (!nid) return resetForm();

                fetch(`${latestUrlBase}/${nid}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data || !data.found) resetForm();
                        else applyData(data);
                    })
                    .catch(resetForm);
            });
        }

        /* ===============================
         * SUBMIT FORM (ISI HIDDEN DI SINI)
         * =============================== */
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // isi semua hidden SEKALI SAAT SUBMIT
                document.querySelectorAll('.uang').forEach(input => {
                    const hidden = document.getElementById(input.id.replace('_fmt', ''));
                    if (!hidden) return;

                    hidden.value = input.value
                        .replace(/\./g, '')
                        .replace(',', '.') || 0;
                });

                const nominal = document.getElementById('nominal_gaji').value;
                if (!nominal || Number(nominal) <= 0) {
                    alert('Nominal gaji wajib diisi');
                    return;
                }

                btnSubmit.disabled = true;
                if (loadingOverlay) loadingOverlay.style.display = 'flex';

                this.submit();
            });
        }

        /* ===============================
         * DEFAULT DATE
         * =============================== */
        const dateInput = document.querySelector('input[name="tanggal_berlaku"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }

    });
</script>