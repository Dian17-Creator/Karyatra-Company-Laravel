<!-- 🟢 Modal Tambah Kontrak -->
<div class="modal fade" id="addContractModal" tabindex="-1" aria-labelledby="addContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('schedule.contract.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="addContractModalLabel">Tambah Kontrak Baru</h5>
                </div>

                <div class="modal-body">

                    {{-- Pegawai --}}
                    <div class="mb-3">
                        <label>Pegawai</label>
                        <select name="nuserid" class="form-select" required>
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->nid }}">{{ $user->cname }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal Mulai & Akhir --}}
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Mulai</label>
                            <input type="date" id="contract_start" name="dstart" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Tanggal Akhir</label>
                            <input type="date" id="contract_end" name="dend" class="form-control" required>
                        </div>
                    </div>

                    {{-- Durasi & Tipe Kontrak --}}
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3">
                            <label>Durasi Kontrak</label>
                            <select id="contract_duration" name="nterm" class="form-select" required>
                                <option value="">-- Pilih Durasi --</option>
                                <option value="3">3 Bulan</option>
                                <option value="6">6 Bulan</option>
                                <option value="12">12 Bulan</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Tipe Kontrak</label>
                            <select name="ctermtype" class="form-select" required>
                                <option value="probation">Probation</option>
                                <option value="promotion">Promotion</option>
                                <option value="evaluation">Evaluation</option>
                            </select>
                        </div>
                    </div>

                </div> {{-- end modal-body --}}

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
