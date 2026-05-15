<!-- Source image (uploaded): /mnt/data/61e6e63f-88c3-4c3b-99b0-7a23ef6edb68.png -->

<!-- Modal Edit Payroll -->
<div class="modal fade" id="modalEditPayroll" tabindex="-1" aria-labelledby="modalEditPayrollLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditPayroll" method="POST" data-url="{{ route('gaji.update', ':id') }}">
                @csrf
                @method('PUT') {{-- akan diubah dinamis sesuai id --}}

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEditPayrollLabel">Edit Payroll</h5>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="user_id" id="mp_user_id">

                    <div class="row g-3">

                        {{-- FIELD INPUT DIPISAH --}}
                        @include('penggajian.modals.modal_edit_fields')

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('formEditPayroll');
        const reasonInput = document.getElementById('mp_reasonedit');
        const errorReason = document.getElementById('error-reasonedit');

        // semua input payroll (kecuali reason & note)
        const watchedInputs = form.querySelectorAll(
            'input[name]:not([name="reasonedit"]):not([name="_token"]):not([name="_method"])'
        );

        // simpan nilai awal
        watchedInputs.forEach(input => {
            input.dataset.original = input.value ?? '';
        });

        form.addEventListener('submit', function(e) {
            let isChanged = false;

            watchedInputs.forEach(input => {
                const original = input.dataset.original ?? '';
                const current = input.value ?? '';

                if (original !== current) {
                    isChanged = true;
                }
            });

            // kalau ada perubahan tapi alasan kosong
            if (isChanged && reasonInput.value.trim() === '') {
                e.preventDefault();

                errorReason.textContent = 'Alasan edit wajib diisi jika ada perubahan data.';
                reasonInput.classList.add('is-invalid');
                reasonInput.focus();
            } else {
                errorReason.textContent = '';
                reasonInput.classList.remove('is-invalid');
            }
        });

    });
</script>
