@extends('layouts.app')

@section('content')

<div class="container mt-4">
    @if ($authUser->fsuper == 1 || $authUser->fhrd == 1)
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manajemen Jadwal & Shift</h2>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addShiftModal">
            + Tambah Shift Baru
        </button>
    </div>
    @endif

    {{-- Alert sukses --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if ($authUser->fsuper == 1 || $authUser->fhrd == 1)
    {{-- Tabel daftar shift --}}
    @include('schedule.component.master_shift')
    @endif

    {{-- Form assign jadwal ke user --}}
    @include('schedule.component.master_assign_shift')

    {{-- Tabel daftar shift user --}}
    @include('schedule.component.user_shift')

    {{-- Daftar Kontrak Kerja Karyawan --}}
    @if (auth()->user()->fhrd == 1)
    @include('schedule.component.master_contract_user')
    @endif
</div>

@include('schedule.modal.modal_tambah_shift')
@include('schedule.modal.modal_tambah_kontrak')
@include('schedule.modal.modal_import_jadwal')
@include('schedule.modal.modal_tambah_agenda')

<script>
    window.appBaseUrl = "{{ url('') }}".replace(/^http:\/\//i, window.location.protocol + '//');
</script>

<link rel="stylesheet" href="{{ asset('css/schedule.css') }}?v=3">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="{{ asset('js/schedule2.js') }}?v={{ time() }}"></script>

@push('scripts')
<script src="{{ asset('js/schedule.js') }}?v={{ time() }}"></script>

@if (session('edit_shift_id'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const modalId = "editShiftModal{{ session('edit_shift_id') }}";
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        }
    });
</script>
@endif
@endpush
@endsection