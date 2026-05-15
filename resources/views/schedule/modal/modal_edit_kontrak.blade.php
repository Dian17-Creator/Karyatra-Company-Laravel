<div class="modal fade" id="editContractModal{{ $contract->nid }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('schedule.contract.update', $contract->nid) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Edit Kontrak</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Pegawai</label>
                        <select name="nuserid" class="form-select" required>
                            @foreach ($users as $user)
                                <option value="{{ $user->nid }}"
                                    {{ $contract->nuserid == $user->nid ? 'selected' : '' }}>
                                    {{ $user->cname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="dstart" class="form-control"
                                value="{{ \Carbon\Carbon::parse($contract->dstart)->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Akhir</label>
                            <input type="date" name="dend" class="form-control"
                                value="{{ \Carbon\Carbon::parse($contract->dend)->format('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Durasi</label>
                            <select name="nterm" class="form-select">
                                <option value="3" {{ $contract->nterm == 3 ? 'selected' : '' }}>3 bulan
                                </option>
                                <option value="6" {{ $contract->nterm == 6 ? 'selected' : '' }}>6 bulan
                                </option>
                                <option value="12" {{ $contract->nterm == 12 ? 'selected' : '' }}>12 bulan
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tipe Kontrak</label>
                            <select name="ctermtype" class="form-select">
                                <option value="probation" {{ $contract->ctermtype == 'probation' ? 'selected' : '' }}>
                                    Probation</option>
                                <option value="promotion" {{ $contract->ctermtype == 'promotion' ? 'selected' : '' }}>
                                    Promotion</option>
                                <option value="evaluation"
                                    {{ $contract->ctermtype == 'evaluation' ? 'selected' : '' }}>
                                    Evaluation</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select name="cstatus" class="form-select">
                            <option value="active" {{ $contract->cstatus == 'active' ? 'selected' : '' }}>
                                Aktif
                            </option>
                            <option value="terminated" {{ $contract->cstatus == 'terminated' ? 'selected' : '' }}>
                                Dihentikan</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success text-white">Simpan
                        Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>
