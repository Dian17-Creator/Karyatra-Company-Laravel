<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel Backoffice') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}?v=3">
</head>

<body>
    {{-- Navbar utama --}}
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #B63352;">
        <div class="container d-flex align-items-center">

            {{-- tombol toggler mobile di KIRI: buka offcanvas dari kiri --}}
            <button class="navbar-toggler d-lg-none me-2" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasLeft" aria-controls="offcanvasLeft" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="d-flex align-items-center mx-auto mx-lg-0 gap-2">

                <a class="navbar-brand fw-bold mb-0" href="{{ route('backoffice.index') }}">
                    Matahati Backoffice
                </a>

                {{-- 🔔 NOTIFIKASI MOBILE --}}
                <a href="#" class="position-relative text-white d-lg-none" data-bs-toggle="modal"
                    data-bs-target="#notifModal" id="notifButtonMobileTop">

                    <i class="bi bi-bell-fill mobile-bell-icon"></i>

                    <span id="notifBadgeMobileTop"
                        class="position-absolute top-0 start-100 translate-middle
                     badge rounded-pill bg-danger"
                        style="font-size:0.65rem; display:none;">
                        0
                    </span>
                </a>
            </div>


            {{-- Versi desktop: tampilkan menu horizontal hanya di >= lg --}}
            <div class="d-none d-lg-flex w-100 justify-content-end">
                <ul class="navbar-nav ms-auto align-items-center">

                    {{-- 🔔 Ikon Notifikasi --}}
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="modal"
                            data-bs-target="#notifModal" id="notifButton">
                            <i class="bi bi-bell-fill notification-icon"></i>
                            <span class="notif-badge" id="notifBadge"></span>
                        </a>
                    </li>

                    <div class="nav-divider"></div>

                    {{-- Menu HOME --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('backoffice.index') ? 'fw-bold text-white' : '' }}"
                            href="{{ route('backoffice.index') }}">HOME</a>
                    </li>

                    <div class="nav-divider"></div>

                    {{-- JADWAL --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('schedule.index') ? 'fw-bold text-white' : '' }}"
                            href="{{ route('schedule.index') }}">JADWAL</a>
                    </li>

                    <div class="nav-divider"></div>

                    {{-- ABSENSI --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('attendance.index') ? 'fw-bold text-white' : '' }}"
                            href="{{ route('attendance.index') }}">ABSENSI</a>
                    </li>

                    <div class="nav-divider"></div>

                    {{-- GAJI --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('penggajian.index') ? 'fw-bold text-white' : '' }}"
                            href="{{ route('penggajian.index') }}">GAJI</a>
                    </li>

                    <div class="nav-divider"></div>

                    {{-- FACES --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('hr.face_approval.index') ? 'fw-bold text-white' : '' }}"
                            href="{{ route('hr.face_approval.index') }}">FACES</a>
                    </li>

                    <div class="nav-divider"></div>

                    {{-- Tombol Logout --}}
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-logout">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Offcanvas (untuk mobile: muncul dari KIRI) -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasLeft" aria-labelledby="offcanvasLeftLabel">
            <!-- OFFCANVAS HEADER (REPLACE EXISTING) -->
            <div class="offcanvas-header"
                style="background-color: #B63352; color: #fff; display:flex; align-items:center; padding:0 1rem;">
                <div class="container">
                    <button id="internalToggler" type="button" class="btn btn-link p-0 me-3 offcanvas-hamburger"
                        data-bs-dismiss="offcanvas" aria-label="Tutup menu" style="color: white;">
                        <!-- gunakan bootstrap icon agar selalu tampil -->
                        <i style="font-size: 100px;" class="bi bi-list" aria-hidden="true"></i>
                    </button>
                </div>

            </div>
            <div class="offcanvas-body d-flex flex-column p-3">

                <div class="container">

                    <nav class="menu-list">

                        <a href="{{ route('backoffice.index') }}"
                            class="menu-item {{ request()->routeIs('backoffice.index') ? 'active' : '' }}">
                            Home
                        </a>

                        <a href="{{ route('schedule.index') }}"
                            class="menu-item {{ request()->routeIs('schedule.index') ? 'active' : '' }}">
                            Jadwal
                        </a>

                        <a href="{{ route('attendance.index') }}"
                            class="menu-item {{ request()->routeIs('attendance.index') ? 'active' : '' }}">
                            Absensi
                        </a>

                        <a href="{{ route('penggajian.index') }}"
                            class="menu-item {{ request()->routeIs('penggajian.index') ? 'active' : '' }}">
                            Gaji
                        </a>

                        <a href="{{ route('hr.face_approval.index') }}"
                            class="menu-item {{ request()->routeIs('hr.face_approval.index') ? 'active' : '' }}">
                            Faces
                        </a>

                        <form action="{{ route('logout') }}" method="POST" class="w-100">
                            @csrf
                            <button type="submit" class="logout-btn-fixed">Logout</button>
                        </form>

                    </nav>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal Popup Notifikasi --}}
    <div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">
                <div class="modal-header" style="background-color: #B63352; color: white;">
                    <h5 class="modal-title" id="notifModalLabel">Notifikasi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body" id="notifBody">
                    <p class="text-center text-muted">Memuat notifikasi...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- konten halaman --}}
    <div class="container mt-4">
        @yield('content')
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const notifBody = document.getElementById('notifBody');
            const notifBadge = document.getElementById('notifBadge');
            const notifBadgeMobile = document.getElementById('notifBadgeMobile');
            const notifBadgeMobileTop = document.getElementById('notifBadgeMobileTop');
            const notifModal = document.getElementById('notifModal');

            // 🧱 Container untuk toast (pojok kanan atas)
            const toastContainer = document.createElement('div');
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = 30000;
            document.body.appendChild(toastContainer);

            let lastCount = 0;
            let pollingActive = true;

            // 🎉 Fungsi tampilkan toast
            window.showToast = function(message) {
                console.log('🎉 showToast terpanggil:', message);

                const toastEl = document.createElement('div');
                toastEl.className =
                    'toast align-items-center text-bg-primary border-0 mb-2 shadow-lg toast-animated';
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body fw-semibold">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;

                toastContainer.appendChild(toastEl);
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 4000
                });
                toast.show();

                // Hilangkan elemen setelah animasi
                setTimeout(() => toastEl.remove(), 4500);
            };

            // 📩 Fungsi ambil notifikasi dari backend
            async function loadNotifications(showToastFlag = false) {
                try {
                    const response = await fetch("{{ route('notifikasi.index') }}", {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) throw new Error("HTTP " + response.status);

                    const data = await response.json();
                    const count = data.count;

                    // Update badge jumlah (desktop & mobile)
                    if (notifBadge) {
                        notifBadge.style.display = count > 0 ? 'inline' : 'none';
                        notifBadge.innerText = count;
                    }
                    if (notifBadgeMobile) {
                        notifBadgeMobile.style.display = count > 0 ? 'inline' : 'none';
                        notifBadgeMobile.innerText = count;
                    }
                    if (notifBadgeMobileTop) {
                        notifBadgeMobileTop.style.display = count > 0 ? 'inline' : 'none';
                        notifBadgeMobileTop.innerText = count;
                    }

                    // Jika ada notifikasi baru → tampilkan toast
                    if (showToastFlag && count > lastCount) {
                        const newNotif = data.notifications.find(n => n.type === 'contract');
                        if (newNotif) {
                            window.showToast(`🕓 ${newNotif.message}`);
                        } else {
                            window.showToast(`🔔 Ada notifikasi baru`);
                        }
                    }

                    // Update isi modal
                    notifBody.innerHTML = '';
                    if (!data.notifications || data.notifications.length === 0) {
                        notifBody.innerHTML = '<p class="text-center text-muted">Belum ada notifikasi.</p>';
                        lastCount = count;
                        return;
                    }

                    let html = '<div class="list-group">';
                    data.notifications.forEach(item => {
                        const time = new Date(item.time).toLocaleString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });

                        html += `
                        <a href="${item.url}" class="list-group-item list-group-item-action mb-2 d-block"
                        style="text-decoration:none; color:inherit;">
                            <div class="fw-bold">${item.message}</div>
                            <small class="text-muted">${time}</small>
                        </a>
                `;
                    });
                    html += '</div>';
                    notifBody.innerHTML = html;

                    lastCount = count;
                } catch (err) {
                    console.error('❌ Gagal memuat notifikasi:', err);
                    notifBody.innerHTML = '<p class="text-center text-danger">Gagal memuat notifikasi.</p>';
                }
            }

            let isLoading = false;

            async function startPolling() {
                while (pollingActive) {

                    if (!isLoading) {
                        isLoading = true;

                        await loadNotifications(true);

                        isLoading = false;
                    }

                    await new Promise(resolve => setTimeout(resolve, 10000));
                }
            }

            notifModal.addEventListener('show.bs.modal', function() {
                console.log('📬 Modal dibuka → refresh data notifikasi');
                loadNotifications(false);
            });

            loadNotifications(false);
            //startPolling();
        });
    </script>

    @stack('scripts')
</body>

</html>
