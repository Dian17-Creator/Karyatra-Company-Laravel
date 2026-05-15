@extends('layouts.app')

@section('content')

    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" /> --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* [id^="map_"] {
                                                                                                                                                height: 200px;
                                                                                                                                                width: 200px;
                                                                                                                                                border-radius: 10px;
                                                                                                                                            } */

        th a {
            color: inherit;
            text-decoration: none;
            transition: 0.2s;
        }

        th a:hover {
            color: #FF6F51;
        }

        .active-sort {
            color: #FF6F51;
        }

        td>div[id^="map_"] {
            margin: 0 auto;
            display: block;
        }
    </style>

    <div class="container py-4">
        <h3 class="text-center">LOG ABSENSI - {{ $user->cname }}</h3>

        {{-- Filter tanggal --}}
        <form method="GET" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">

                    <div class="row g-2 align-items-end">

                        <div class="col">
                            <label class="small text-muted">Dari</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                value="{{ request('start_date', request('date', now()->subMonth()->format('Y-m-d'))) }}">
                        </div>

                        <div class="col-auto">
                            <div class="date-sync-icon" onclick="syncLogDate()" title="Samakan tanggal">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>

                        <div class="col">
                            <label class="small text-muted">Sampai</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                value="{{ request('end_date', request('date', now()->format('Y-m-d'))) }}">
                        </div>

                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                </div>

                <div class="col-md-4 d-flex justify-content-end">
                    <a href="{{ route('export-mscan', [
                        'user_id' => $user->nid,
                        'start_date' => request('start_date', now()->subMonth()->format('Y-m-d')),
                        'end_date' => request('end_date', now()->format('Y-m-d')),
                    ]) }}"
                        class="btn btn-success fw-bold">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel - {{ $user->cname }}
                    </a>
                </div>
            </div>
        </form>

        {{-- Tabel log --}}
        <table class="table table-bordered align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th>ID</th>
                    <th>Waktu</th>
                    <th style="max-width: 50px;">Lokasi</th>
                    <th>Alasan</th>
                    <th style="white-space: nowrap;">Tipe Absen</th>
                    <th style="white-space: nowrap;">Status Approval</th>
                    {{-- <th>Peta</th> --}}
                    <th>Foto</th>
                    <th>Device User</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr class="text-center align-top">
                        <td>{{ $log->nid }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->dscanned)->format('d/m/Y H:i:s') }}</td>

                        {{-- Lokasi --}}
                        <td>
                            @if (!empty($log->cplacename))
                                {{ $log->cplacename }}
                            @elseif (!empty($log->nlat) && !empty($log->nlng))
                                {{ $log->nlat }}, {{ $log->nlng }}
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>

                        <td>{{ $log->creason ?? '-' }}</td>

                        {{-- 🔸 Jenis absen --}}
                        <td>
                            @switch($log->source)
                                @case('face')
                                    <span class="badge bg-info text-white">Face</span>
                                @break

                                @case('forgot')
                                    <span class="badge bg-danger text-white">Lupa Absen</span>
                                @break

                                @case('manual')
                                    <span class="badge bg-warning text-dark">Manual</span>
                                @break

                                @default
                                    <span class="badge bg-primary">Scan</span>
                            @endswitch
                        </td>

                        {{-- 🔹 Status Approval --}}
                        <td>
                            {{-- ================= FACE ================= --}}
                            @if ($log->source === 'face')
                                <span class="badge bg-success">Accepted</span>

                                {{-- ================= FORGOT (HRD ONLY) ================= --}}
                            @elseif ($log->source === 'forgot')
                                @php
                                    $approved = $log->cstatus === 'approved';
                                    $rejected = $log->cstatus === 'rejected';
                                @endphp

                                @if ($approved)
                                    <span class="badge bg-success">Approved by HRD</span>
                                @elseif ($rejected)
                                    <span class="badge bg-danger">Rejected by HRD</span>
                                @else
                                    @if (auth()->user()->fhrd == 1)
                                        <form action="{{ route('mscan.approve.hrd', $log->nid) }}" method="POST"
                                            class="approve-form d-inline">
                                            @csrf
                                            <button name="status" value="approved"
                                                class="btn btn-success btn-sm">Approve</button>
                                            <button name="status" value="rejected"
                                                class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">Waiting HRD</span>
                                    @endif
                                @endif

                                {{-- ================= MANUAL ================= --}}
                            @elseif ($log->source === 'manual' || (!empty($log->fmanual) && $log->fmanual == 1))
                                @php
                                    $isCaptain =
                                        auth()->user()->fadmin == 1 &&
                                        auth()->user()->fsuper == 0 &&
                                        auth()->user()->fhrd == 0;

                                    $isHrd = auth()->user()->fhrd == 1;

                                    $requestOwnerIsCaptain = $log->fadmin == 1 && $log->fhrd == 0;

                                    $hasBeenProcessed =
                                        ($log->cstatus ?? 'pending') !== 'pending' ||
                                        ($log->chrdstat ?? 'pending') !== 'pending';
                                @endphp

                                {{-- ✅ Kalau sudah diproses --}}
                                @if ($hasBeenProcessed)
                                    @php
                                        $cApproved = $log->cstatus === 'approved';
                                        $cRejected = $log->cstatus === 'rejected';
                                        $hApproved = $log->chrdstat === 'approved';
                                        $hRejected = $log->chrdstat === 'rejected';
                                    @endphp

                                    <span
                                        class="badge
                                        @if ($hApproved || $cApproved) bg-success
                                        @elseif ($hRejected || $cRejected) bg-danger
                                        @else bg-secondary @endif">

                                        {{-- ✅ PRIORITAS HRD --}}
                                        @if ($hApproved)
                                            Approved by HRD
                                        @elseif ($hRejected)
                                            Rejected by HRD

                                            {{-- ✅ CAPTAIN --}}
                                        @elseif ($cApproved)
                                            Approved by Captain
                                        @elseif ($cRejected)
                                            Rejected by Captain
                                        @else
                                            Pending
                                        @endif
                                    </span>
                                @else
                                    {{-- 🔥 Jika dibuat oleh Captain --}}
                                    @if ($requestOwnerIsCaptain)
                                        @if ($isHrd)
                                            <form action="{{ route('mscan.approve.hrd', $log->nid) }}" method="POST"
                                                class="approve-form d-inline">
                                                @csrf
                                                <button name="status" value="approved"
                                                    class="btn btn-success btn-sm">Approve</button>
                                                <button name="status" value="rejected"
                                                    class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                Pending by HRD
                                            </span>
                                        @endif

                                        {{-- 🔥 Jika dibuat oleh crew biasa --}}
                                    @elseif ($isCaptain)
                                        <form action="{{ route('mscan.approve.captain', $log->nid) }}" method="POST"
                                            class="approve-form d-inline">
                                            @csrf
                                            <button name="status" value="approved"
                                                class="btn btn-success btn-sm">Approve</button>
                                            <button name="status" value="rejected"
                                                class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    @elseif ($isHrd)
                                        <form action="{{ route('mscan.approve.hrd', $log->nid) }}" method="POST"
                                            class="approve-form d-inline">
                                            @csrf
                                            <button name="status" value="approved"
                                                class="btn btn-success btn-sm">Approve</button>
                                            <button name="status" value="rejected"
                                                class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                @endif
                            @else
                                <span class="badge bg-success">Accepted</span>
                            @endif
                        </td>

                        {{-- 🗺️ Peta --}}
                        {{-- <td>
                            <div id="map_{{ $log->nid }}"></div>
                        </td> --}}

                        {{-- 📸 Foto --}}
                        <td>
                            @if (!empty($log->cphoto_path))
                                @php
                                    //$photoUrl = 'https://absensi.matahati.my.id/' . ltrim($log->cphoto_path, '/');
                                    $photoUrl = url($log->cphoto_path);
                                @endphp
                                <a href="{{ $photoUrl }}" target="_blank">
                                    <img src="{{ $photoUrl }}" alt="Foto Absen"
                                        style="width:150px;height:150px;object-fit:cover;border-radius:6px;"
                                        onerror="this.onerror=null; this.replaceWith(document.createTextNode('-'));" />
                                </a>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>

                        {{-- Device User --}}
                        <td>
                            @if (!empty($log->cdevstring ?? null))
                                @php
                                    $raw = trim($log->cdevstring);
                                    $lower = strtolower($raw);

                                    // default aman
                                    $brand = '-';
                                    $model = '-';
                                    $os = '-';

                                    // =========================
                                    // 🔥 1. JSON (WEB CHROME)
                                    // =========================
                                    if (str_starts_with($raw, '{')) {
                                        $json = json_decode($raw, true);

                                        if (!is_array($json)) {
                                            $json = json_decode(stripslashes($raw), true);
                                        }

                                        if (is_array($json)) {
                                            $brand = $json['manufacturer'] ?? ($json['brand'] ?? 'Android');
                                            $model = $json['model'] ?? '-';
                                            $os = $json['os_version'] ?? ($json['os'] ?? '-');
                                        }
                                    }

                                    // =========================
                                    // 🔥 2. IPHONE (SAFARI / WEBVIEW)
                                    // =========================
                                    elseif (str_contains($lower, 'iphone')) {
                                        $brand = 'Apple';
                                        $model = 'iPhone';

                                        preg_match('/iphone os ([\d_]+)/', $lower, $matches);
                                        $os = isset($matches[1]) ? 'iOS ' . str_replace('_', '.', $matches[1]) : 'iOS';
                                    }

                                    // =========================
                                    // 🔥 3. MAC (SAFARI / CHROME)
                                    // =========================
                                    elseif (str_contains($lower, 'macintosh') || str_contains($lower, 'mac os x')) {
                                        $brand = 'Apple';
                                        $model = 'Mac';

                                        preg_match('/mac os x ([\d_]+)/', $lower, $matches);
                                        $os = isset($matches[1])
                                            ? 'macOS ' . str_replace('_', '.', $matches[1])
                                            : 'macOS';
                                    }

                                    // =========================
                                    // 🔥 4. ANDROID APK (|)
                                    // =========================
                                    elseif (str_contains($raw, '|')) {
                                        $parts = explode('|', $raw);

                                        $model = trim($parts[0] ?? '-');
                                        $brand = trim($parts[1] ?? 'Android');
                                        $os = trim($parts[2] ?? '-');
                                    }

                                    // =========================
                                    // 🔥 5. ANDROID WEB (CHROME / BROWSER)
                                    // =========================
                                    elseif (str_contains($lower, 'android')) {
                                        $brand = 'Android';

                                        preg_match('/android ([\d\.]+)/', $lower, $matchOs);
                                        $os = isset($matchOs[1]) ? 'Android ' . $matchOs[1] : 'Android';

                                        preg_match('/android [^;]+; ([^;\)]+)/i', $raw, $matchModel);
                                        $model = trim($matchModel[1] ?? '-');
                                    }
                                @endphp

                                <div class="device-box">
                                    <span class="badge bg-primary">{{ ucfirst($brand) }}</span>
                                    <span class="badge bg-secondary">{{ $model }}</span>
                                    <span class="badge bg-dark">
                                        <i
                                            class="bi
                    {{ str_contains($lower, 'iphone') || str_contains($lower, 'mac') ? 'bi-apple' : 'bi-android' }}">
                                        </i>
                                        {{ $os }}
                                    </span>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-center mt-4">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- 🔹 SweetAlert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const forms = document.querySelectorAll("form.approve-form");

            forms.forEach(form => {
                form.addEventListener("submit", async function(e) {
                    e.preventDefault();

                    const clickedButton = e.submitter;
                    const formData = new FormData(this);
                    formData.append("status", clickedButton.value);

                    const url = this.action;
                    const statusCell = this.closest('td');
                    const buttons = this.querySelectorAll("button");

                    buttons.forEach(btn => {
                        btn.disabled = true;
                        btn.innerText = "Processing...";
                    });

                    try {
                        const response = await fetch(url, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]').content
                            }
                        });

                        const result = await response.json();

                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: result.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            // ubah status langsung
                            statusCell.innerHTML = `
                                <span class="badge ${clickedButton.value === 'approved' ? 'bg-success' : 'bg-danger'}">
                                    ${clickedButton.value === 'approved' ? 'Approved' : 'Rejected'}
                                </span>`;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: result.message ||
                                    'Terjadi kesalahan saat memperbarui status.'
                            });
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Gagal memproses permintaan.'
                        });
                    } finally {
                        buttons.forEach(btn => btn.disabled = false);
                    }
                });
            });
        });
    </script>

    <script>
        function syncLogDate() {
            const start = document.getElementById("start_date");
            const end = document.getElementById("end_date");

            if (start && end) {
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


    {{-- 🗺️ Leaflet Map --}}
    {{-- <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach ($logs as $log)
                (function() {
                    const mapId = 'map_{{ $log->nid }}';
                    const div = document.getElementById(mapId);
                    if (!div) return;

                    const lat = parseFloat('{{ $log->nlat ?? 0 }}');
                    const lng = parseFloat('{{ $log->nlng ?? 0 }}');
                    if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                        div.innerHTML = '<small class="text-muted">Lokasi tidak tersedia</small>';
                        return;
                    }

                    setTimeout(() => {
                        const map = L.map(mapId).setView([lat, lng], 17);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19
                        }).addTo(map);
                        L.marker([lat, lng]).addTo(map).bindPopup('📍 Lokasi Absen');
                        map.invalidateSize();
                    }, 250);
                })();
            @endforeach
        });
    </script> --}}
@endsection
