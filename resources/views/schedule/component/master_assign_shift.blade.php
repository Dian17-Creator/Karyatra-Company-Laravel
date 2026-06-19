<div class="card mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span>Atur Shift Karyawan</span>
        <!-- Tombol Import jadi buka modal -->
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importScheduleModal">
            + Import Jadwal User
        </button>
    </div>
    <div class="card-body">
        <form action="{{ route('schedule.generate') }}" method="POST" id="scheduleForm">
            @csrf
            <div class="row mb-3 align-items-end">
                <div class="col-lg-4 col-md-6 col-sm-12 mb-2 mb-lg-0">
                    <label>Pilih Karyawan</label>
                    <select name="nuserid" class="form-select" required>
                        <option value="">-- pilih karyawan --</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->nid }}">{{ $user->cname }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12 mb-2 mb-lg-0">
                    <div class="row g-2 align-items-end">

                        <div class="col">
                            <label class="text-muted">Dari</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>

                        <div class="col-auto">
                            <div class="date-sync-icon" onclick="syncScheduleDate()" title="Samakan tanggal">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>

                        <div class="col">
                            <label class="text-muted">Sampai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>

                    </div>
                </div>

                <div class="col-lg-2 col-md-6 col-sm-12 mb-2 mb-lg-0">
                    <label>Filter Kalender</label>
                    <select id="filterPeriode" class="form-select">
                        <option value="">-- Pilih Periode --</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="last_week">Minggu Lalu</option>
                        <option value="next_week">Minggu Depan</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="next_month">Bulan Depan</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6 col-sm-12 mb-2 mb-lg-0">
                    <button type="submit" class="btn btn-success w-100">Atur</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function syncScheduleDate() {
        const start = document.getElementById('start_date');
        const end = document.getElementById('end_date');

        if (start && end && start.value) {
            end.value = start.value;
        }
    }
</script>

<style>
    .date-sync-icon {
        font-size: 18px;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
    }

    .date-sync-icon:hover {
        color: #00770c;
        transform: scale(1.15);
    }
</style>