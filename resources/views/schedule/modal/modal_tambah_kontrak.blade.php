<!-- 🟢 Modal Tambah Kontrak -->
<div class="modal fade" id="addContractModal" tabindex="-1" aria-labelledby="addContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('schedule.contract.store') }}" method="POST" style="width: 100%;">
            @csrf
            <div class="modal-content border-0 shadow" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header text-white" style="background-color: #0f7643; border-bottom: none; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 18px 24px;">
                    <h5 class="modal-title" id="addContractModalLabel" style="font-weight: 600; font-size: 1.2rem;">
                        Tambah Kontrak Baru
                    </h5>
                </div>

                <div class="modal-body" style="padding: 24px;">

                    {{-- Pegawai --}}
                    <div class="mb-3">
                        <label class="form-label-custom">Pegawai</label>
                        <select name="nuserid" class="form-select-custom" required>
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->nid }}">{{ $user->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal Mulai & Akhir --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Tanggal Mulai</label>
                            <input type="date" id="contract_start" name="dstart" class="form-control-custom" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Tanggal Akhir</label>
                            <input type="date" id="contract_end" name="dend" class="form-control-custom" required>
                        </div>
                    </div>

                    {{-- Durasi & Tipe Kontrak --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Durasi Kontrak</label>
                            <select id="contract_duration" name="nterm" class="form-select-custom" required>
                                <option value="">-- Pilih Durasi --</option>
                                <option value="3">3 Bulan</option>
                                <option value="6">6 Bulan</option>
                                <option value="12">12 Bulan</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-custom">Tipe Kontrak</label>
                            <select name="ctermtype" class="form-select-custom" required>
                                <option value="probation">Probation</option>
                                <option value="promotion">Promotion</option>
                                <option value="evaluation">Evaluation</option>
                            </select>
                        </div>
                    </div>

                </div> {{-- end modal-body --}}

                <div class="modal-footer d-flex justify-content-between w-100 gap-3" style="background-color: #eef1f4; border-top: none; padding: 18px 24px 22px 24px; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn-shift-cancel flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-shift-submit flex-fill">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@once
<style>
    .form-label-custom {
        font-size: 0.9rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
        display: inline-block;
    }

    .form-control-custom,
    .form-select-custom {
        display: block;
        width: 100%;
        padding: 10px 14px;
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        appearance: none;
        border-radius: 8px;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }

    .form-select-custom {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px 12px;
        padding-right: 36px;
    }

    .form-control-custom:focus,
    .form-select-custom:focus {
        color: #212529;
        background-color: #fff;
        border-color: #0f7643;
        outline: 0;
        box-shadow: 0 0 0 3px rgba(15, 118, 67, 0.15);
    }

    .btn-shift-cancel {
        background-color: #4e545c;
        color: #fff;
        border: 1px solid #4e545c;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn-shift-cancel:hover {
        background-color: #3f444a;
        border-color: #3f444a;
        color: #fff;
    }

    .btn-shift-submit {
        background-color: #0f7643;
        color: #fff;
        border: 1px solid #0f7643;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn-shift-submit:hover {
        background-color: #157347;
        border-color: #157347;
        color: #fff;
    }
</style>
@endonce
