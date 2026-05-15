<div class="modal fade" id="editShiftModal{{ $shift->nid }}" tabindex="-1"
    aria-labelledby="editShiftModalLabel{{ $shift->nid }}" aria-hidden="true">

    @if ($errors->has('split') && session('edit_shift_id') == $shift->nid)
        <div class="alert alert-danger">
            {{ $errors->first('split') }}
        </div>
    @endif

    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ url('/schedule/' . $shift->nid) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">Edit Shift</h5>
                </div>

                <div class="modal-body">

                    {{-- TIPE SHIFT --}}
                    <div class="mb-3">
                        <label>Tipe Shift</label>
                        <select name="ctype" class="form-select shift-type" data-target="{{ $shift->nid }}">
                            <option value="normal" {{ $shift->ctype === 'normal' ? 'selected' : '' }}>
                                Normal
                            </option>
                            <option value="flexi" {{ $shift->ctype === 'flexi' ? 'selected' : '' }}>
                                Fleksibel
                            </option>
                        </select>
                    </div>

                    {{-- NAMA --}}
                    <div class="mb-3">
                        <label>Nama Shift</label>
                        <input type="text" name="cname" class="form-control" value="{{ $shift->cname }}" required>
                    </div>

                    {{-- FLEXI --}}
                    <div class="flexi-box-{{ $shift->nid }}"
                        style="{{ $shift->ctype === 'flexi' ? '' : 'display:none' }}">
                        <div class="mb-3">
                            <label>Total Jam Kerja</label>
                            <input type="number" name="ctotal" class="form-control" min="1" max="24"
                                value="{{ $shift->ctotal }}">
                        </div>
                    </div>

                    {{-- NORMAL --}}
                    <div class="normal-box-{{ $shift->nid }}"
                        style="{{ $shift->ctype === 'normal' ? '' : 'display:none' }}">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Jam Mulai</label>
                                <input type="time" name="dstart" class="form-control"
                                    value="{{ $shift->dstart ? substr($shift->dstart, 0, 5) : '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Jam Selesai</label>
                                <input type="time" name="dend" class="form-control"
                                    value="{{ $shift->dend ? substr($shift->dend, 0, 5) : '' }}">
                            </div>
                        </div>

                        <div class="d-flex align-items-center my-3">
                            <div class="flex-grow-1 border-top"></div>
                            <span class="mx-3 text-muted">SPLIT</span>
                            <div class="flex-grow-1 border-top"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Jam Mulai</label>
                                <input type="time" name="dstart2" class="form-control"
                                    value="{{ $shift->dstart2 ? substr($shift->dstart2, 0, 5) : '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Jam Selesai</label>
                                <input type="time" name="dend2" class="form-control"
                                    value="{{ $shift->dend2 ? substr($shift->dend2, 0, 5) : '' }}">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        Simpan
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

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
