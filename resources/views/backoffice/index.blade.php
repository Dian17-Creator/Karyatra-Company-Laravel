@extends('layouts.app')

@php
    // === GUARD VARIABLE (WAJIB) ===
    $departments = $departments ?? collect();
    $rekenings = $rekenings ?? collect();
    $devices = $devices ?? collect();
    $admins = $admins ?? collect();
@endphp

@section('content')
    <div class="container mt-4">
        <h3 style="margin-bottom: 15px;">Dashboard Backoffice</h3>

        @include('backoffice.calendar')

        @include('backoffice.modal.modal_tanggal')

        @include('backoffice.modal.modal_tambah_kontrak')

        @include('backoffice.modal.modal_tambah_department')

        @include('backoffice.modal.modal_tambah_agenda')

        @include('backoffice.modal.modal_detail_agenda')

        {{-- ====== HEADER ====== --}}
        <div class="d-flex justify-content-end mb-3">
            @if (auth()->user()->fhrd == 1)
                <button class="btn btn-primary btn-sm" data-bs-target="#addDepartmentModal" data-bs-toggle="modal">
                    + Tambah Departemen
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @include('backoffice.component.master_department')

        {{-- ====== TABEL USER ====== --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-grow-1">

                @if (auth()->user()->fhrd == 1)
                    <!-- Filter Department -->
                    <select id="departmentFilter" class="form-select form-select-sm" style="max-width:170px;">
                        <option value="">Semua Department</option>
                        @foreach ($departments as $dept)
                            <option value="{{ strtolower($dept->cname) }}">
                                {{ $dept->cname }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- Filter Status User -->
                <select id="statusFilter" class="form-select form-select-sm" style="max-width:115px;">
                    <option value="1" selected>User Aktif</option>
                    <option value="0">User Nonaktif</option>
                    <option value="">Semua User</option>
                </select>

                <!-- Search -->
                <div class="position-relative flex-grow-1">
                    <input type="text" id="searchInput" class="form-control form-control-sm"
                        placeholder="Cari user (nama, email, cabang, role)...">

                    <button id="clearSearch"
                        class="btn btn-sm btn-light position-absolute top-50 end-0 translate-middle-y me-2"
                        style="border: none; display:none;">✖</button>
                </div>
            </div>

            @if (auth()->user()->fhrd == 1)
                <div class="d-inline-flex gap-2">
                    <button style="background-color: green" class="btn btn-success btn-sm" data-bs-toggle="modal"
                        data-bs-target="#addUserModal">
                        + Tambah User
                    </button>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importFingerprintModal"
                        style="color: rgb(39, 39, 39)">
                        Import Fingerprint
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendSlipModal">
                        Kirim Slip Gaji
                    </button>
                    <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#exportUserModal">
                        Export User
                    </button>
                </div>
            @endif
        </div>

        @include('backoffice.component.master_user')

        @include('backoffice.device')

    </div>

    @include('backoffice.modal.modal_send_slip')

    @include('backoffice.modal.modal_tambah_department')

    @include('backoffice.modal.modal_tambah_user')

    @include('backoffice.modal.modal_import_fingerprint')

    @include('backoffice.modal.modal_export_user')

    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v=3">
    <script src="{{ asset('js/home.js') }}?v={{ time() }}"></script>
    <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 12000;"></div>
@endsection
