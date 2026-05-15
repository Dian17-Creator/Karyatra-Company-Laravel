{{-- Modal Tambah Shift --}}
<div class="modal fade" id="addShiftModal" tabindex="-1" aria-labelledby="addShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ url('/schedule') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="addShiftModalLabel">Tambah Shift Baru</h5>
                </div>

                <div class="modal-body">

                    {{-- TIPE SHIFT --}}
                    <div class="mb-3">
                        <label>Tipe Shift</label>
                        <select name="ctype" id="shiftType" class="form-select" required>
                            <option value="normal" selected>Reguler</option>
                            <option value="flexi">Fleksibel</option>
                        </select>
                    </div>

                    {{-- NAMA SHIFT --}}
                    <div class="mb-3">
                        <label>Nama Shift</label>
                        <input type="text" name="cname" class="form-control" required>
                    </div>

                    {{-- REGULER FORM --}}
                    <div id="regularFields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Jam Mulai</label>
                                <input type="time" name="dstart" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Jam Selesai</label>
                                <input type="time" name="dend" class="form-control">
                            </div>

                            <div class="d-flex align-items-center my-3">
                                <div class="flex-grow-1 border-top border-secondary"></div>
                                <span class="mx-3 fw-semibold text-secondary">SPLIT</span>
                                <div class="flex-grow-1 border-top border-secondary"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Jam Mulai</label>
                                <input type="time" name="dstart2" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Jam Selesai</label>
                                <input type="time" name="dend2" class="form-control">
                            </div>
                        </div>
                    </div>

                    {{-- FLEXI FORM --}}
                    <div id="flexiFields" class="d-none">
                        <div class="mb-3">
                            <label>Total Jam Kerja (per hari)</label>
                            <input type="text" name="ctotal" class="form-control" min="1" max="24"
                                placeholder="Contoh : 9">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('shiftType');
        const regular = document.getElementById('regularFields');
        const flexi = document.getElementById('flexiFields');

        if (!typeSelect) return;

        function toggleShiftForm() {
            if (typeSelect.value === 'flexi') {
                regular.classList.add('d-none');
                flexi.classList.remove('d-none');
            } else {
                regular.classList.remove('d-none');
                flexi.classList.add('d-none');
            }
        }

        toggleShiftForm(); // default saat modal dibuka
        typeSelect.addEventListener('change', toggleShiftForm);
    });
</script>
