@extends('layouts.app')

@section('title', 'Face User')

@section('content')
    <div class="container py-4">

        <h4 class="fw-bold mb-3">
            Face Terdaftar – {{ $user->cname }}
        </h4>

        <div class="text-muted mb-4">
            {{ $user->department?->cname }}
        </div>

        <div class="d-flex flex-wrap gap-3">
            @forelse ($faces as $face)
                @php
                    // 🔥 URL ABSOLUT (SAMA DENGAN LOG ABSENSI)
                    //$faceUrl = 'https://absensi.matahati.my.id/faces/' . ltrim($face->cfilename, '/');
                    $faceUrl = url('karyatrahrd/biometrik/' . $face->cfilename);
                @endphp

                <div class="rounded-4 overflow-hidden" style="width:200px;height:240px;box-shadow:0 4px 12px rgba(0,0,0,.1)">
                    <a href="{{ $faceUrl }}" target="_blank">
                        <img src="{{ $faceUrl }}" alt="Face User" class="w-100 h-100" style="object-fit:cover"
                            onerror="this.onerror=null; this.replaceWith(document.createTextNode('-'));">
                    </a>
                </div>
            @empty
                <div class="text-muted fst-italic">
                    Face belum tersedia
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            <a href="{{ route('backoffice.index') }}" class="btn btn-secondary">
                ← Kembali
            </a>
        </div>

    </div>
@endsection
