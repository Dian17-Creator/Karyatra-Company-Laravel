{{-- resources/views/backoffice/partials/modal_tambah_user.blade.php --}}
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('backoffice.add') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Tambah User</h5>
                </div>

                <div class="modal-body">
                    <div class="row gx-3">
                        <div class="col-md-3 mb-3">
                            <label>Username</label>
                            <input type="text" name="email" class="form-control" value="{{ old('email') }}"
                                required>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label>Gmail</label>
                            <input type="email" name="cmailaddress" class="form-control"
                                value="{{ old('cmailaddress') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>No. Telepon</label>
                            <input type="text" name="cphone" class="form-control" value="{{ old('cphone') }}">
                        </div>

                        <div class="col-md-5 mb-3">
                            <label>No. KTP</label>
                            <input type="text" name="cktp" class="form-control" value="{{ old('cktp') }}">
                        </div>

                        {{-- Finger ID --}}
                        <div class="col-md-4 mb-3">
                            <label>Finger ID (ID Mesin Fingerprint)</label>
                            <input type="text" name="finger_id" class="form-control" value="{{ old('finger_id') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Nama</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                required>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="cfullname" class="form-control" value="{{ old('cfullname') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Tanggal Masuk</label>
                            <input type="date" name="dtanggalmasuk" class="form-control"
                                value="{{ old('dtanggalmasuk') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nomor Rekening</label>
                            <input type="text" name="caccnumber" class="form-control" value="{{ old('caccnumber') }}"
                                placeholder="">
                        </div>

                        {{-- Prepare rekenings --}}
                        @php
                        $allReks = isset($rekenings) ? $rekenings : collect();

                        // unik berdasarkan bank + nomor_rekening
                        $uniqueReks = $allReks
                        ->unique(function ($r) {
                        $bank = isset($r->bank) ? strtolower(trim($r->bank)) : '';
                        $nom = isset($r->nomor_rekening)
                        ? preg_replace('/\D+/', '', (string) $r->nomor_rekening)
                        : '';
                        return $bank . '|' . $nom;
                        })
                        ->values();

                        $mandiriReks = $uniqueReks
                        ->filter(function ($r) {
                        return isset($r->bank) && strtolower(trim($r->bank)) === 'mandiri';
                        })
                        ->values();

                        $currentBank = old('bank', '');
                        $currentRekeningId = old('rekening_id', '');
                        @endphp

                        {{-- Jenis bank (enum: BCA, BRI, Mandiri) --}}
                        <div class="col-md-6 mb-3">
                            <label>Jenis Bank</label>
                            <select name="bank" id="bankSelect" class="form-control">
                                <option value="">-- Pilih Jenis Bank --</option>
                                <option value="Mandiri"
                                    {{ strcasecmp($currentBank, 'Mandiri') === 0 ? 'selected' : '' }}>Mandiri</option>
                                <option value="BCA" {{ strcasecmp($currentBank, 'BCA') === 0 ? 'selected' : '' }}>
                                    BCA</option>
                                <option value="BRI" {{ strcasecmp($currentBank, 'BRI') === 0 ? 'selected' : '' }}>
                                    BRI</option>
                            </select>
                        </div>

                        {{-- Jika Mandiri -> tampilkan dropdown rekening sumber --}}
                        <div class="mb-3" id="mandiriRekeningWrapper" style="display: none;">
                            <label>Pilih Rekening Sumber (Mandiri)</label>
                            <select name="rekening_id" id="rekeningSelect" class="form-control">
                                <option value="">-- Pilih Rekening --</option>

                                @if ($mandiriReks->count())
                                @foreach ($mandiriReks as $rek)
                                @php
                                $nom = $rek->nomor_rekening ?? '';
                                $nomDisp = $nom ? preg_replace('/\D+/', '', (string) $nom) : '';
                                $bankLabel = strtoupper($rek->bank ?? '');
                                $atasNama = $rek->atas_nama ?? '';
                                $label = trim(
                                $bankLabel .
                                ($nomDisp ? " - {$nomDisp}" : '') .
                                ($atasNama ? " ({$atasNama})" : ''),
                                );
                                @endphp
                                <option value="{{ $rek->id }}"
                                    {{ (string) $rek->id === (string) $currentRekeningId ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                                @else
                                <option disabled>Belum ada data rekening Mandiri</option>
                                @endif
                            </select>
                        </div>



                    </div>

                    <div class="row gx-2">
                        <div class="col-md-6 mb-3">
                            <label>Departemen</label>
                            <select name="niddept" class="form-control" required>
                                <option value="">-- Pilih Departemen --</option>
                                @foreach ($departments as $dept)
                                <option value="{{ $dept->nid }}"
                                    {{ old('niddept') == $dept->nid ? 'selected' : '' }}>
                                    {{ $dept->cname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Payroll Department</label>
                            <select name="niddeptpayroll" class="form-control" required>
                                <option value="">-- Pilih Departemen --</option>
                                @foreach ($departments as $dept)
                                <option value="{{ $dept->nid }}"
                                    {{ old('niddeptpayroll') == $dept->nid ? 'selected' : '' }}>
                                    {{ $dept->cname }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Role User</label>

                        <div class="role-selector">

                            <input type="radio" id="roleCaptain" name="role" value="fadmin"
                                {{ old('role') === 'fadmin' ? 'checked' : '' }}>

                            <label for="roleCaptain" class="role-card captain">
                                Captain
                            </label>


                            <input type="radio" id="roleSupervisor" name="role" value="fsuper"
                                {{ old('role') === 'fsuper' ? 'checked' : '' }}>

                            <label for="roleSupervisor" class="role-card supervisor">
                                Supervisor
                            </label>


                            <input type="radio" id="roleSenior" name="role" value="fsenior"
                                {{ old('role') === 'fsenior' ? 'checked' : '' }}>

                            <label for="roleSenior" class="role-card senior">
                                Senior Crew
                            </label>


                            <input type="radio" id="roleCrew" name="role" value="crew"
                                {{ old('role', 'crew') === 'crew' ? 'checked' : '' }}>

                            <label for="roleCrew" class="role-card crew">
                                Crew
                            </label>

                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between w-100 gap-2">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success flex-fill">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .role-selector {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .role-selector input {
        display: none;
    }

    .role-card {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
        border: 2px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        background: #f8f9fa;
        transition: all .2s;
        width: 100%;

    }

    .role-card:hover {
        background: #e9ecef;
    }

    /* CAPTAIN - HIJAU */
    .role-selector input:checked+.role-card.captain {
        border-color: #198754;
        background: #198754;
        color: white;
    }

    /* SUPERVISOR - BIRU */
    .role-selector input:checked+.role-card.supervisor {
        border-color: #0d6efd;
        background: #0d6efd;
        color: white;
    }

    /* SENIOR CREW - KUNING */
    .role-selector input:checked+.role-card.senior {
        border-color: #ffc107;
        background: #ffc107;
        color: #000;
    }

    /* CREW - ABU GELAP */
    .role-selector input:checked+.role-card.crew {
        border-color: #6c757d;
        background: #6c757d;
        color: white;
    }

    .role-card.captain {}

    .role-card.supervisor {}

    .role-card.senior {}

    .role-card.crew {}
</style>

{{-- JS: toggle tampilnya dropdown rekening sumber ketika bank = Mandiri --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bankSelect = document.getElementById('bankSelect');
        const mandiriWrapper = document.getElementById('mandiriRekeningWrapper');
        const rekeningSelect = document.getElementById('rekeningSelect');
        const addModal = document.getElementById('addUserModal');

        function isMandiri(value) {
            return String(value || '').toLowerCase() === 'mandiri';
        }

        function toggleMandiriDropdown() {
            if (!bankSelect) return;
            if (isMandiri(bankSelect.value)) {
                mandiriWrapper.style.display = '';
                if (rekeningSelect) rekeningSelect.setAttribute('required', 'required');
            } else {
                mandiriWrapper.style.display = 'none';
                if (rekeningSelect) {
                    rekeningSelect.removeAttribute('required');
                    // kosongkan pilihan supaya tidak tersubmit nilai lama
                    rekeningSelect.value = '';
                }
            }
        }

        // inisialisasi saat DOM ready dan saat modal dibuka
        toggleMandiriDropdown();
        if (bankSelect) bankSelect.addEventListener('change', toggleMandiriDropdown);

        if (addModal) {
            addModal.addEventListener('show.bs.modal', function() {
                toggleMandiriDropdown();
            });
        }
    });
</script>