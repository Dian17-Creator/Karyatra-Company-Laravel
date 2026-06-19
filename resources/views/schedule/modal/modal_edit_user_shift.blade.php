{{-- Single shared modal for editing user shift schedule --}}
<div class="modal fade" id="editUserShiftModal" tabindex="-1"
    aria-labelledby="editUserShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editUserShiftForm" method="POST" class="w-100">
            @csrf
            @method('PUT')

            <div class="modal-content border-0 shadow" style="border-radius: 15px; overflow: hidden;">

                <div class="modal-header text-white" style="background-color: #0f7643; border-bottom: none; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 18px 24px;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.2rem;">Edit Jadwal Shift Karyawan</h5>
                </div>

                <div class="modal-body" style="padding: 24px;">

                    <div class="mb-3">
                        <label class="form-label-custom">Nama Karyawan</label>
                        <input type="text" class="form-control-custom" id="editShiftUserName" disabled style="background-color: #e9ecef; opacity: 0.85;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Tanggal Kerja</label>
                        <input type="text" class="form-control-custom" id="editShiftDate" disabled style="background-color: #e9ecef; opacity: 0.85;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Pilih Shift Baru</label>
                        <select name="nidsched" id="editShiftSelect" class="form-select-custom" required>
                            @foreach($allMasters as $m)
                            <option value="{{ $m->nid }}">
                                {{ $m->cname }} ({{ $m->dstart ? substr($m->dstart, 0, 5) . ' - ' . substr($m->dend, 0, 5) : ($m->ctotal . ' Jam') }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                </div>

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
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
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