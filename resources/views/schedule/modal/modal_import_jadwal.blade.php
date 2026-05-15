{{-- Modal Import Jadwal --}}

<div class="modal fade" id="importScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form id="importScheduleForm" action="{{ route('file-import-schedule') }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-upload me-2"></i>Import Jadwal Kerja
                    </h5>
                </div>

                <div class="modal-body">
                    {{-- Notifikasi --}}
                    @if ($errors->has('file'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ $errors->first('file') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($sukses = Session::get('userschedulesuccess'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <strong>{{ $sukses }}</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($warnings = Session::get('userschedule_warning'))
                        <div class="alert alert-warning alert-dismissible fade show">
                            <ul class="mb-0">
                                @foreach ($warnings as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="text-center">
                        <h5 class="mb-3">Pilih File Jadwal (format .xlsx)</h5>
                        <div class="form-group" style="max-width: 500px; margin: 0 auto;">
                            <div class="custom-file">
                                <input type="file" name="file" class="custom-file-input" id="customFile"
                                    accept=".xlsx" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Import Data</button>
                </div>

            </form>

        </div>
    </div>
</div>
