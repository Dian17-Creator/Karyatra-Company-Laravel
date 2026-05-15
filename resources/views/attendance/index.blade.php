@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-end mb-2">
            <a href="#" id="exportBtn" class="btn btn-success fw-semibold">
                <i class="fas fa-file-excel me-1"></i> Export Laporan
            </a>
        </div>

        <div class="card shadow-sm">

            <div class="card-header d-flex align-items-center bg-secondary text-white">
                <!-- Kiri -->
                <h4 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Laporan Absensi
                </h4>

                <!-- Spacer -->
                <div class="ms-auto d-flex align-items-center gap-2">
                    <!-- Filter Status Absen (baru) -->
                    <button id="attStatusBtn" class="btn btn-primary fw-semibold" data-bs-toggle="modal"
                        data-bs-target="#attStatusModal">
                        <i class="fas fa-user-check me-1"></i>
                        <span id="attStatusLabel">Sudah Absen</span>
                    </button>

                    <!-- Filter Departemen -->
                    @if (auth()->check() && auth()->user()->fhrd == 2)
                        <button id="deptFilterBtn" class="btn btn-primary fw-semibold" data-bs-toggle="modal"
                            data-bs-target="#deptFilterModal">
                            <i class="fas fa-building me-1"></i>
                            <span id="deptFilterLabel">Semua Departemen</span>
                        </button>
                    @endif

                    <!-- Filter Tanggal -->
                    <button id="filterBtn" class="btn btn-primary fw-semibold" data-bs-toggle="modal"
                        data-bs-target="#dateFilterModal">
                        <i class="fas fa-filter me-1"></i>
                        <span id="filterLabel">Hari ini</span>
                    </button>
                </div>
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
                        <table class="table table-bordered table-striped align-middle text-center table-attendance">
                            <thead class="table-dark">
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

    @include('attendance.modal.modal_filter_department')

    @include('attendance.modal.modal_filter_date')

    @include('attendance.modal.modal_filter_status')

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
@endsection
