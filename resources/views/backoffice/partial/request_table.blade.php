@extends('layouts.app')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
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

        .badge {
            font-size: 0.85rem;
        }
    </style>

    <div class="container py-4">
        <h3 class="text-center mb-4">IZIN TIDAK MASUK - {{ $user->cname }}</h3>

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('export-request', [
                'user_id' => $user->nid,
                'start_date' => request('start_date', now()->subMonth()->format('Y-m-d')),
                'end_date' => request('end_date', now()->format('Y-m-d')),
            ]) }}"
                class="btn btn-success fw-bold">
                <i class="bi bi-file-earmark-excel"></i> Export Excel - {{ $user->cname }}
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">

                <thead class="table-light text-center align-top">
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                        <th>Lokasi</th>
                        <th>Status Approval</th>
                        <th>Foto</th>
                        <th>Device</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($requests as $req)
                        <tr class="text-center align-top">
                            <td>{{ $req->nid }}</td>
                            <td>{{ \Carbon\Carbon::parse($req->drequest)->format('d/m/Y') }}</td>
                            <td>{{ $req->creason }}</td>

                            {{-- Lokasi --}}
                            <td>
                                @if (!empty($req->cplacename))
                                    {{ $req->cplacename }}
                                @else
                                    {{ $req->nlat }}, {{ $req->nlng }}
                                @endif
                            </td>

                            {{-- 🔹 Status Approval --}}
                            <td>
                                @php
                                    $isCaptain =
                                        auth()->user()->fadmin == 1 &&
                                        auth()->user()->fsuper == 0 &&
                                        auth()->user()->fhrd == 0;
                                    $isHrd = auth()->user()->fhrd == 1;

                                    // Tentukan status akhir gabungan
                                    $finalStatus = 'pending';
                                    $finalBy = '';

                                    if ($req->fadmreq == 1) {
                                        $finalStatus = 'approved';
                                        $finalBy = 'Admin';
                                    } elseif ($req->cstatus === 'rejected') {
                                        $finalStatus = 'rejected';
                                        $finalBy = 'Captain';
                                    } elseif ($req->chrdstat === 'rejected') {
                                        $finalStatus = 'rejected';
                                        $finalBy = 'HRD';
                                    } elseif ($req->cstatus === 'approved' && $req->chrdstat === 'approved') {
                                        $finalStatus = 'approved';
                                        $finalBy = 'Captain & HRD';
                                    } elseif ($req->cstatus === 'approved') {
                                        $finalStatus = 'approved';
                                        $finalBy = 'Captain';
                                    } elseif ($req->chrdstat === 'approved') {
                                        $finalStatus = 'approved';
                                        $finalBy = 'HRD';
                                    }
                                @endphp

                                {{-- ✅ Jika dibuat admin langsung auto-approved --}}
                                @if ($req->fadmreq == 1)
                                    <span class="badge bg-success">Approved (By Admin)</span>

                                    {{-- ✅ Jika sudah ada hasil final --}}
                                @elseif ($finalStatus !== 'pending')
                                    <span class="badge {{ $finalStatus === 'approved' ? 'bg-success' : 'bg-danger' }}">
                                        {{ ucfirst($finalStatus) }} by {{ $finalBy }}
                                    </span>

                                    {{-- ✅ Jika masih pending, tampilkan tombol sesuai role --}}
                                @else
                                    @php
                                        $requestOwnerIsCaptain =
                                            $req->user && $req->user->fadmin == 1 && $req->user->fhrd == 0;
                                    @endphp

                                    {{-- 🔥 Jika request dibuat oleh Captain --}}
                                    @if ($requestOwnerIsCaptain)
                                        @if ($isHrd)
                                            <form action="{{ route('mrequest.approve.hrd', $req->nid) }}" method="POST"
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

                                        {{-- 🔥 Jika request dibuat oleh crew biasa --}}
                                    @elseif ($isCaptain)
                                        <form action="{{ route('mrequest.approve.captain', $req->nid) }}" method="POST"
                                            class="approve-form d-inline">
                                            @csrf
                                            <button name="status" value="approved"
                                                class="btn btn-success btn-sm">Approve</button>
                                            <button name="status" value="rejected"
                                                class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    @elseif ($isHrd)
                                        <form action="{{ route('mrequest.approve.hrd', $req->nid) }}" method="POST"
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
                            </td>

                            {{-- Foto --}}
                            <td>
                                @if (!empty($req->cphoto_path))
                                    @php
                                        //$photoUrl = 'https://absensi.matahati.my.id/' . ltrim($req->cphoto_path, '/');
                                        $photoUrl = url($req->cphoto_path);
                                    @endphp
                                    <a href="{{ $photoUrl }}" target="_blank">
                                        <img src="{{ $photoUrl }}" alt="Foto Request"
                                            style="width:150px;height:150px;object-fit:cover;border-radius:6px;"
                                            onerror="this.onerror=null; this.replaceWith(document.createTextNode('-'));" />
                                    </a>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>

                            <td>
                                @if ($req->cdevstring)
                                    @php
                                        $raw = trim($req->cdevstring);
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
                                            $os = isset($matches[1])
                                                ? 'iOS ' . str_replace('_', '.', $matches[1])
                                                : 'iOS';
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
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $requests->links('pagination::bootstrap-5') }}
        </div>
    </div>

    {{-- 🧠 Script Approval SweetAlert --}}
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

                            // Ganti tombol dengan badge status
                            statusCell.innerHTML = `
                                <span class="badge ${clickedButton.value === 'approved' ? 'bg-success' : 'bg-danger'}">
                                    ${clickedButton.value === 'approved' ? 'Approved' : 'Rejected'}
                                </span>
                            `;
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

    <style>
        .device-box {
            display: flex;
            flex-direction: column;
            /* 🔥 ini kuncinya */
            align-items: center;
            gap: 4px;
        }

        .device-box .badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 8px;
            min-width: 90px;
            /* biar rata panjangnya */
            text-align: center;
        }
    </style>
@endsection
