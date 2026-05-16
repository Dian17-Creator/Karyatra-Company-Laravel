@extends('layouts.app')

@section('title', 'HR Approval – Registrasi Wajah')

@section('content')
    <div class="py-4">
        <div class="container-fluid px-4 px-lg-5"> {{-- ✅ Lebar layar maksimal --}}

            {{-- Judul --}}
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold mb-2" style="color:#ff6f51; margin-top: -25px;">
                        HR Approval – Registrasi Wajah
                    </h2>
                    <p class="text-muted mb-0">
                        Tinjau dan setujui registrasi wajah karyawan sebelum dapat digunakan untuk absensi.
                    </p>
                </div>
            </div>

            {{-- Card User --}}
            <div class="row g-4">
                @foreach ($users as $u)
                    <div class="col-12 col-xl-10 mx-auto"> {{-- ✅ Lebar card diperlebar --}}
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h4 class="mb-1 fw-semibold">{{ $u->cname }}</h4>

                                        {{-- tampilkan DEPARTEMEN / CABANG tanpa label apa pun --}}
                                        @if (!empty($u->department?->cname))
                                            <div class="text-muted small">
                                                {{ $u->department->cname }}
                                            </div>
                                        @endif
                                    </div>

                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        Menunggu persetujuan
                                    </span>
                                </div>

                                {{-- FOTO --}}
                                <div class="d-flex flex-wrap gap-3 mb-4">
                                    @foreach ($u->faces as $face)
                                        <div class="rounded-4 overflow-hidden"
                                            style="width: 180px; height: 220px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                                            <img src="/karyatrahrd/biometrik/{{ $face->cfilename }}" class="w-100 h-100"
                                                style="object-fit: cover;">
                                        </div>
                                    @endforeach
                                </div>

                                {{-- TOMBOL --}}
                                <div class="d-flex justify-content-end gap-3">
                                    <form action="{{ route('hr.face_approval.approve', $u->nid) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success px-4">
                                            ✓ Approve
                                        </button>
                                    </form>

                                    <form action="{{ route('hr.face_approval.reject', $u->nid) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger px-4">
                                            ✕ Reject
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endsection
