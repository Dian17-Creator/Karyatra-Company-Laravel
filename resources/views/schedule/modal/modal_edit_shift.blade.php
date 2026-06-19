<div class="modal fade" id="editShiftModal{{ $shift->nid }}" tabindex="-1"
    aria-labelledby="editShiftModalLabel{{ $shift->nid }}" aria-hidden="true">

    @if ($errors->has('split') && session('edit_shift_id') == $shift->nid)
    <div class="alert alert-danger">
        {{ $errors->first('split') }}
    </div>
    @endif

    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ url('/schedule/' . $shift->nid) }}" method="POST" style="width: 100%;">
            @csrf
            @method('PUT')

            <div class="modal-content border-0 shadow" style="border-radius: 15px; overflow: hidden;">

                <div class="modal-header text-white" style="background-color: #0f7643; border-bottom: none; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 18px 24px;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.2rem;">
                        Edit Shift
                    </h5>
                </div>

                <div class="modal-body" style="padding: 24px;">

                    {{-- TIPE SHIFT --}}
                    <div class="mb-3">
                        <label class="form-label-custom">Tipe Shift</label>
                        <select name="ctype" class="form-select-custom shift-type" data-target="{{ $shift->nid }}">
                            <option value="normal" {{ $shift->ctype === 'normal' ? 'selected' : '' }}>
                                Reguler
                            </option>
                            <option value="flexi" {{ $shift->ctype === 'flexi' ? 'selected' : '' }}>
                                Fleksibel
                            </option>
                        </select>
                    </div>

                    {{-- NAMA --}}
                    <div class="mb-3">
                        <label class="form-label-custom">Nama Shift</label>
                        <input type="text" name="cname" class="form-control-custom" value="{{ $shift->cname }}" required>
                    </div>

                    {{-- FLEXI --}}
                    <div class="flexi-box-{{ $shift->nid }}"
                        style="{{ $shift->ctype === 'flexi' ? '' : 'display:none' }}">
                        <div class="mb-3">
                            <label class="form-label-custom">Total Jam Kerja (per hari)</label>
                            <input type="number" name="ctotal" class="form-control-custom" min="1" max="24"
                                value="{{ $shift->ctotal }}">
                        </div>
                    </div>

                    {{-- NORMAL --}}
                    <div class="normal-box-{{ $shift->nid }}"
                        style="{{ $shift->ctype === 'normal' ? '' : 'display:none' }}">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Jam Mulai</label>
                                <input type="time" name="dstart" class="form-control-custom"
                                    value="{{ $shift->dstart ? substr($shift->dstart, 0, 5) : '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Jam Selesai</label>
                                <input type="time" name="dend" class="form-control-custom"
                                    value="{{ $shift->dend ? substr($shift->dend, 0, 5) : '' }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center my-4">
                                    <div class="flex-grow-1 border-top" style="border-color: #dee2e6 !important;"></div>
                                    <span class="mx-3 fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 1.5px; text-transform: uppercase;">Split Shift (Opsional)</span>
                                    <div class="flex-grow-1 border-top" style="border-color: #dee2e6 !important;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Jam Mulai (Split)</label>
                                <input type="time" name="dstart2" class="form-control-custom"
                                    value="{{ $shift->dstart2 ? substr($shift->dstart2, 0, 5) : '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Jam Selesai (Split)</label>
                                <input type="time" name="dend2" class="form-control-custom"
                                    value="{{ $shift->dend2 ? substr($shift->dend2, 0, 5) : '' }}">
                            </div>
                        </div>

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

<script>
    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('shift-type')) return;

        const id = e.target.dataset.target;
        const type = e.target.value;

        const normalBox = document.querySelector('.normal-box-' + id);
        const flexiBox = document.querySelector('.flexi-box-' + id);

        if (type === 'flexi') {
            normalBox.style.display = 'none';
            flexiBox.style.display = 'block';
        } else {
            normalBox.style.display = 'block';
            flexiBox.style.display = 'none';
        }
    });
</script>
@endonce