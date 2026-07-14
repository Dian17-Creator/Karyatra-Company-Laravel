@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">HALAMAN ABSENSI</h4>
        <div class="d-flex gap-2">
            <!-- Filter Status Absen -->
            <select id="attendanceStatusSelect" class="form-select fw-semibold" style="height: 38px; width: auto; min-width: 150px;" onchange="updateAttendanceStatus(this.value)">
                <option value="present" selected>✅ Sudah Absen</option>
                <option value="missing">❌ Belum Absen</option>
            </select>

            <!-- Filter Departemen -->
            @if (auth()->check() && auth()->user()->fhrd == 1)
            <select id="deptId" class="form-select fw-semibold" style="height: 38px; width: auto; min-width: 190px;" onchange="loadAttendance()">
                <option value="">📍 Semua Departemen</option>
                @foreach ($departments as $dept)
                <option value="{{ $dept->nid }}">{{ $dept->cname }}</option>
                @endforeach
            </select>
            @endif

            <!-- Filter Tanggal -->
            <button id="filterBtn" class="btn btn-outline-success" style="height:38px;" data-bs-toggle="modal"
                data-bs-target="#dateFilterModal">
                📅 <span id="filterLabel">Hari ini</span>
            </button>

            <!-- Export Laporan -->
            <a href="#" id="exportBtn" class="btn btn-success fw-semibold d-inline-flex align-items-center" style="height: 38px;">
                <i class="fas fa-file-excel me-1"></i> Export Laporan
            </a>
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-header d-flex align-items-center bg-danger text-white">
            <!-- Kiri -->
            <h4 class="mb-0">
                <i class="fas fa-calendar-alt me-2" style="color: #1c0505ff;"></i>Laporan Absensi
            </h4>
        </div>


        <div class="card-body">
            <div class="table-scroll">
                {{-- Loading Spinner --}}
                <div id="loading" class="loading text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>

                {{-- Error Alert --}}
                <div id="errorAlert" class="alert alert-danger d-none" role="alert"></div>

                {{-- Tabel Absensi --}}
                <div class="table-responsive" style="max-height:600px; overflow-y:auto;">
                    <table class="table table-bordered align-middle text-center table-attendance">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Jadwal/Shift</th>

                                <th>Jam Masuk</th>
                                <th>Jam Checkin</th>
                                <th>Jam Keluar</th>
                                <th>Jam Checkout</th>

                                <th>Jam Masuk (Split)</th>
                                <th>Jam Checkin (Split)</th>
                                <th>Jam Keluar (Split)</th>
                                <th>Jam Checkout (Split)</th>

                                <th>Keterlambatan (Menit)</th>
                                <th>Lembur (Menit)</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody">
                            <tr>
                                <td colspan="14" class="text-center text-muted">
                                    Klik <b>"Refresh"</b> untuk memuat data absensi
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Controls --}}
            <div id="paginationControls" class="d-flex justify-content-between align-items-center mt-3 d-none">
                <button id="prevBtn" class="btn btn-outline-secondary btn-sm" disabled>← Prev</button>
                <span id="pageInfo" class="fw-semibold"></span>
                <button id="nextBtn" class="btn btn-outline-secondary btn-sm">Next →</button>
            </div>
        </div>
    </div>
</div>



@include('attendance.modal.modal_filter_date')

<script>
    window.APP = {
        attendanceUrl: "{{ route('attendance.report') }}",
        attendanceMissingUrl: "{{ route('attendance.missing') }}",
        exportUrl: "{{ route('export-attendance-report') }}"
    };
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .table-attendance {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin: 0;
        /* ⬅️ tidak lagi geser ke kanan */
        min-width: 1700px;
        /* boleh diubah sesuai kebutuhan kolom */
    }

    .table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .loading {
        display: none;
    }

    .table {
        margin-bottom: 0;
        width: 100%;
    }

    .table thead th {
        top: 0;
        background-color: #212529 !important;
        color: #fff;
        z-index: 10;
    }

    .text-late {
        color: #dc3545 !important;
        font-weight: bold;
    }

    .card.shadow-sm {
        margin-bottom: 30px !important;
    }
</style>


<script src="{{ asset('js/attendance.js') }}?v={{ time() }}"></script>
<script>
    function updateAttendanceStatus(value) {
        attendanceStatus = value;
        loadAttendance();
    }
</script>
@endsection