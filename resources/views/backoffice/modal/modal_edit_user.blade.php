{{-- resources/views/backoffice/partials/modal_edit_user.blade.php --}}
<div class="modal fade" id="editUserModal{{ $user->nid }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('backoffice.updateUser', $user->nid) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Edit User</h5>
                </div>

                <div class="modal-body">
                    <div class="row gx-2">
                        {{-- Username --}}
                        <div class="col-md-3 mb-3">
                            <label>Username</label>
                            <input type="text" name="email" class="form-control"
                                value="{{ old('email', $user->cemail) }}" required>
                        </div>

                        {{-- Gmail --}}
                        <div class="col-md-5 mb-3">
                            <label>Gmail</label>
                            <input type="email" name="cmailaddress" class="form-control"
                                value="{{ old('cmailaddress', $user->cmailaddress) }}" placeholder="">
                        </div>

                        {{-- Password --}}
                        <div class="col-md-4 mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control"
                                placeholder="Kosongkan jika tidak diubah">
                        </div>

                        {{-- No Telepon --}}
                        <div class="col-md-3 mb-3">
                            <label>No. Telepon</label>
                            <input type="text" name="cphone" class="form-control"
                                value="{{ old('cphone', $user->cphone) }}" placeholder="">
                        </div>

                        {{-- KTP --}}
                        <div class="col-md-5 mb-3">
                            <label>No. KTP</label>
                            <input type="text" name="cktp" class="form-control"
                                value="{{ old('cktp', $user->cktp) }}" placeholder="">
                        </div>

                        {{-- Finger ID --}}
                        <div class="col-md-4 mb-3">
                            <label>Finger ID (ID Mesin Fingerprint)</label>
                            <input type="text" name="finger_id" class="form-control"
                                value="{{ old('finger_id', $user->finger_id) }}">
                        </div>

                        {{-- Nama --}}
                        <div class="col-md-3 mb-3">
                            <label>Nama</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $user->cname) }}" required>
                        </div>

                        {{-- Nama Lengkap --}}
                        <div class="col-md-5 mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="cfullname" class="form-control"
                                value="{{ old('cfullname', $user->cfullname) }}" placeholder="">
                        </div>

                        {{-- Tanggal Masuk --}}
                        <div class="col-md-4 mb-3">
                            <label>Tanggal Masuk</label>
                            <input type="date" name="dtanggalmasuk" class="form-control"
                                value="{{ old('dtanggalmasuk', $user->dtanggalmasuk ? \Carbon\Carbon::parse($user->dtanggalmasuk)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nomor Rekening</label>
                            <input type="text" name="caccnumber" class="form-control"
                                value="{{ old('caccnumber', $user->caccnumber) }}">
                        </div>



                        {{-- Prepare rekening collections (dedup & filter) --}}
                        @php
                        $allReks = isset($rekenings) ? $rekenings : collect();

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

                        // preferensi: old() -> muser.bank -> muser->rekening->bank
                        $currentBank = old('bank') ?? ($user->bank ?? ($user->rekening->bank ?? ''));
                        $currentRekeningId = old('rekening_id') ?? ($user->rekening_id ?? '');
                        $nid = $user->nid;
                        @endphp

                        {{-- Jenis Bank --}}
                        <div class="col-md-6 mb-3">
                            <label>Jenis Bank</label>
                            <select name="bank" id="bankSelect{{ $nid }}" class="form-control">
                                <option value="">-- Pilih Jenis Bank --</option>
                                <option value="Mandiri"
                                    {{ strcasecmp($currentBank, 'Mandiri') === 0 ? 'selected' : '' }}>Mandiri</option>
                                <option value="BCA" {{ strcasecmp($currentBank, 'BCA') === 0 ? 'selected' : '' }}>
                                    BCA</option>
                                <option value="BRI" {{ strcasecmp($currentBank, 'BRI') === 0 ? 'selected' : '' }}>
                                    BRI</option>
                                <option value="Lainnya"
                                    {{ strcasecmp($currentBank, 'Lainnya') === 0 ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>

                        {{-- Rekening Sumber (hanya Mandiri) --}}
                        <div class="col-12 mb-3" id="mandiriRekeningWrapper{{ $nid }}" style="display: none;">
                            <label>Pilih Rekening Sumber (Mandiri)</label>
                            <select name="rekening_id" id="rekeningSelect{{ $nid }}" class="form-control">
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
                        {{-- Departemen --}}
                        <div class="col-md-6 mb-3">
                            <label>Departemen</label>
                            <select name="niddept" class="form-control" required>
                                @foreach ($departments as $dept)
                                <option value="{{ $dept->nid }}"
                                    {{ $user->niddept == $dept->nid ? 'selected' : '' }}>
                                    {{ $dept->cname }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Payroll Departemen</label>
                            <select name="niddeptpayroll" class="form-control" required>
                                @foreach ($departments as $dept)
                                <option value="{{ $dept->nid }}"
                                    {{ $user->niddeptpayroll == $dept->nid ? 'selected' : '' }}>
                                    {{ $dept->cname }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row gx-2">
                        {{-- Status User --}}
                        <div class="col-md-6 mb-3">
                            <label>Status User</label>
                            <select name="factive" class="form-control" required>
                                <option value="1" {{ $user->factive ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option value="0" {{ !$user->factive ? 'selected' : '' }}>
                                    Nonaktif
                                </option>
                            </select>
                        </div>

                        {{-- Notifikasi --}}
                        <div class="col-md-6 mb-3">
                            <label>Status Notifikasi</label>
                            <select name="fnotif" class="form-control" required>
                                <option value="1" {{ $user->fnotif ? 'selected' : '' }}>
                                    Aktif
                                </option>
                                <option value="0" {{ !$user->fnotif ? 'selected' : '' }}>
                                    Nonaktif
                                </option>
                            </select>
                        </div>
                    </div>

                    {{-- Role --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role User</label>

                        <div class="role-selector">

                            <input type="radio" id="roleCaptain{{ $user->nid }}" name="role" value="fadmin"
                                {{ $user->fadmin ? 'checked' : '' }}>

                            <label for="roleCaptain{{ $user->nid }}" class="role-card captain">
                                Captain
                            </label>


                            <input type="radio" id="roleSupervisor{{ $user->nid }}" name="role"
                                value="fsuper" {{ $user->fsuper ? 'checked' : '' }}>

                            <label for="roleSupervisor{{ $user->nid }}" class="role-card supervisor">
                                Supervisor
                            </label>


                            <input type="radio" id="roleSenior{{ $user->nid }}" name="role" value="fsenior"
                                {{ $user->fsenior ? 'checked' : '' }}>

                            <label for="roleSenior{{ $user->nid }}" class="role-card senior">
                                Senior Crew
                            </label>


                            <input type="radio" id="roleCrew{{ $user->nid }}" name="role" value="crew"
                                {{ !$user->fadmin && !$user->fsuper && !$user->fsenior ? 'checked' : '' }}>

                            <label for="roleCrew{{ $user->nid }}" class="role-card crew">
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