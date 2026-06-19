<div class="card mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span>Daftar Jadwal Shift Karyawan</span>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form id="userShiftFilterForm" method="GET" action="{{ route('schedule.index') }}" class="mb-4">
            <div class="row g-3 align-items-end">
                <!-- Filter Karyawan -->
                <div class="col-lg-3 col-md-6 col-sm-12 mb-2 mb-lg-0">
                    <label class="form-label fw-semibold text-muted">Cari Karyawan</label>
                    <select name="filter_user_id" class="form-select">
                        <option value="">-- Semua Karyawan --</option>
                        @foreach($users as $u)
                        <option value="{{ $u->nid }}" {{ request('filter_user_id') == $u->nid ? 'selected' : '' }}>
                            {{ $u->cname }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Departemen -->
                <div class="col-lg-3 col-md-6 col-sm-12 mb-2 mb-lg-0">
                    <label class="form-label fw-semibold text-muted">Pilih Departemen</label>
                    <select name="filter_dept_id" class="form-select">
                        <option value="">-- Semua Departemen --</option>
                        @foreach($departments as $d)
                        <option value="{{ $d->nid }}" {{ request('filter_dept_id') == $d->nid ? 'selected' : '' }}>
                            {{ $d->cname }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dari & Sampai Tanggal -->
                <div class="col-lg-4 col-md-8 col-sm-12 mb-2 mb-lg-0">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label fw-semibold text-muted">Dari</label>
                            <input type="date" name="filter_start_date" id="filter_start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-auto">
                            <div class="date-sync-icon" onclick="syncUserScheduleDate()" title="Samakan tanggal" style="cursor: pointer;">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold text-muted">Sampai</label>
                            <input type="date" name="filter_end_date" id="filter_end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                    </div>
                </div>

                <!-- Filter Kalender (Periode) -->
                <div class="col-lg-2 col-md-4 col-sm-12 mb-2 mb-lg-0">
                    <label class="form-label fw-semibold text-muted">Filter Kalender</label>
                    <select name="filter_periode" id="filterPeriodeUser" class="form-select">
                        <option value="">-- Pilih Periode --</option>
                        <option value="today" {{ request('filter_periode') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="this_week" {{ request('filter_periode') == 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="last_week" {{ request('filter_periode') == 'last_week' ? 'selected' : '' }}>Minggu Lalu</option>
                        <option value="next_week" {{ request('filter_periode') == 'next_week' ? 'selected' : '' }}>Minggu Depan</option>
                        <option value="this_month" {{ request('filter_periode') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="next_month" {{ request('filter_periode') == 'next_month' ? 'selected' : '' }}>Bulan Depan</option>
                    </select>
                </div>
            </div>
        </form>

        <div id="userShiftTableContainer">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-primary">
                        <tr>
                            <th style="width: 5%; background-color: #ffd8e0ff !important;">No</th>
                            <th style="background-color: #ffd8e0ff !important;">Nama Karyawan</th>
                            <th style="background-color: #ffd8e0ff !important;">Departemen</th>
                            <th style="background-color: #ffd8e0ff !important;">Tanggal</th>
                            <th style="background-color: #ffd8e0ff !important;">Nama Shift</th>
                            <th style="background-color: #ffd8e0ff !important;">Mulai</th>
                            <th style="background-color: #ffd8e0ff !important;">Selesai</th>
                            <th style="background-color: #ffd8e0ff !important;">Mulai (Split)</th>
                            <th style="background-color: #ffd8e0ff !important;">Selesai (Split)</th>
                            <th style="width: 15%; background-color: #ffd8e0ff !important;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userSchedules as $sched)
                        <tr>
                            <td>{{ ($userSchedules->currentPage() - 1) * $userSchedules->perPage() + $loop->iteration }}</td>
                            <td class="text-start">{{ $sched->user->cname ?? '-' }}</td>
                            <td>{{ $sched->user->department->cname ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($sched->dwork)->translatedFormat('d M Y') }}</td>
                            <td>
                                @php
                                $shiftName = trim($sched->cschedname ?? '-');
                                $lowerName = strtolower($shiftName);

                                if (str_contains($lowerName, 'pagi')) {
                                // Soft Green
                                $badgeStyle = 'background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc;';
                                } elseif (str_contains($lowerName, 'siang')) {
                                // Soft Orange/Yellow
                                $badgeStyle = 'background-color: #fff3cd; color: #664d03; border: 1px solid #ffecb5;';
                                } elseif (str_contains($lowerName, 'malam')) {
                                // Soft Purple/Indigo
                                $badgeStyle = 'background-color: #e2e3e5; color: #41464b; border: 1px solid #d3d6d8;';
                                } elseif (str_contains($lowerName, 'split')) {
                                // Soft Pink/Red
                                $badgeStyle = 'background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7;';
                                } elseif (str_contains($lowerName, 'libur') || str_contains($lowerName, 'off')) {
                                // Grey
                                $badgeStyle = 'background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;';
                                } else {
                                // Dynamic beautiful pastel based on name hash
                                $hash = crc32($lowerName);
                                $hue = abs($hash) % 360;
                                $badgeStyle = "background-color: hsl({$hue}, 70%, 92%); color: hsl({$hue}, 80%, 24%); border: 1px solid hsl({$hue}, 50%, 82%);";
                                }
                                $styleAttr = 'style="' . $badgeStyle . ' font-size: 0.8rem; border-radius: 6px; letter-spacing: 0.3px;"';
                                @endphp
                                <span class="badge fw-semibold px-2.5 py-1.5" {!! $styleAttr !!}>
                                    {{ $shiftName }}
                                </span>
                            </td>
                            <td>{{ $sched->dstart ? substr($sched->dstart, 0, 5) : '-' }}</td>
                            <td>{{ $sched->dend ? substr($sched->dend, 0, 5) : '-' }}</td>
                            <td>{{ $sched->dstart2 ? substr($sched->dstart2, 0, 5) : '-' }}</td>
                            <td>{{ $sched->dend2 ? substr($sched->dend2, 0, 5) : '-' }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-warning btn-sm text-white btn-edit-user-shift"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserShiftModal"
                                        data-sched-id="{{ $sched->nid }}"
                                        data-user-name="{{ $sched->user->cname ?? '-' }}"
                                        data-dwork="{{ \Carbon\Carbon::parse($sched->dwork)->translatedFormat('d F Y') }}"
                                        data-nidsched="{{ $sched->nidsched }}">
                                        Edit
                                    </button>

                                    <form action="{{ route('user-schedule.destroy', $sched->nid) }}" method="POST"
                                        onsubmit="return confirm('Hapus jadwal shift ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-muted py-4">Belum ada data jadwal shift karyawan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3 px-2">
                {{ $userSchedules->withQueryString()->links('penggajian.components.custom_pagination') }}
            </div>
        </div>
    </div>

    {{-- Shared Modal Edit User Shift (outside AJAX container) --}}
    @include('schedule.modal.modal_edit_user_shift')
</div>

<script>
    // Populate shared edit modal when opened
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-user-shift');
        if (!btn) return;

        const schedId = btn.dataset.schedId;
        const userName = btn.dataset.userName;
        const dwork = btn.dataset.dwork;
        const nidsched = btn.dataset.nidsched;

        const form = document.getElementById('editUserShiftForm');
        const baseUrl = '{{ url("user-schedule") }}';
        form.action = baseUrl + '/' + schedId;

        document.getElementById('editShiftUserName').value = userName;
        document.getElementById('editShiftDate').value = dwork;

        const select = document.getElementById('editShiftSelect');
        if (select) {
            select.value = nidsched;
        }
    });

    async function fetchFilteredUserShifts(url = null) {
        const form = document.getElementById('userShiftFilterForm');
        const container = document.getElementById('userShiftTableContainer');

        if (!container || !form) return;

        // Show loading state
        container.style.opacity = '0.5';

        let fetchUrl = url;
        if (!fetchUrl) {
            const formData = new FormData(form);
            const searchParams = new URLSearchParams(formData);
            fetchUrl = form.action + '?' + searchParams.toString();
        }

        try {
            const response = await fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await response.text();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContent = doc.getElementById('userShiftTableContainer');
            if (newContent) {
                container.innerHTML = newContent.innerHTML;
            }

            // Update URL bar without reload
            window.history.pushState({}, '', fetchUrl);
        } catch (error) {
            console.error('Error fetching user shifts:', error);
        } finally {
            container.style.opacity = '1';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const filterForm = document.getElementById('userShiftFilterForm');

        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchFilteredUserShifts();
            });

            // Auto-submit on change for inputs (excluding periode since it updates dates first)
            filterForm.addEventListener('change', function(e) {
                if (e.target.name === 'filter_periode') return;
                fetchFilteredUserShifts();
            });
        }
    });

    // Intercept pagination clicks
    document.addEventListener('click', function(e) {
        const paginationLink = e.target.closest('#userShiftTableContainer .custom-page-link');
        if (paginationLink && paginationLink.tagName.toLowerCase() === 'a') {
            e.preventDefault();
            fetchFilteredUserShifts(paginationLink.href);
        }
    });

    function syncUserScheduleDate() {
        const start = document.getElementById('filter_start_date');
        const end = document.getElementById('filter_end_date');

        if (start && end && start.value) {
            end.value = start.value;
        }

        fetchFilteredUserShifts();
    }

    document.addEventListener("DOMContentLoaded", function() {
        const filterUser = document.getElementById("filterPeriodeUser");
        const startDateUser = document.getElementById("filter_start_date");
        const endDateUser = document.getElementById("filter_end_date");

        if (filterUser && startDateUser && endDateUser) {
            filterUser.addEventListener("change", function() {
                const today = new Date();
                let start, end;

                const formatDate = (date) => {
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, "0");
                    const d = String(date.getDate()).padStart(2, "0");
                    return `${y}-${m}-${d}`;
                };

                switch (this.value) {
                    case "today":
                        start = end = today;
                        break;
                    case "this_week":
                        const day = today.getDay();
                        start = new Date(today);
                        start.setDate(today.getDate() - (day === 0 ? 6 : day - 1));
                        end = new Date(start);
                        end.setDate(start.getDate() + 6);
                        break;
                    case "last_week":
                        start = new Date(today);
                        const lastWeekDay = today.getDay();
                        start.setDate(today.getDate() - (lastWeekDay === 0 ? 6 : lastWeekDay - 1) - 7);
                        end = new Date(start);
                        end.setDate(start.getDate() + 6);
                        break;
                    case "next_week":
                        start = new Date(today);
                        const nextWeekDay = today.getDay();
                        start.setDate(today.getDate() - (nextWeekDay === 0 ? 6 : nextWeekDay - 1) + 7);
                        end = new Date(start);
                        end.setDate(start.getDate() + 6);
                        break;
                    case "this_month":
                        start = new Date(today.getFullYear(), today.getMonth(), 1);
                        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        break;
                    case "next_month":
                        start = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                        end = new Date(today.getFullYear(), today.getMonth() + 2, 0);
                        break;
                    default:
                        start = end = null;
                }

                if (start && end) {
                    startDateUser.value = formatDate(start);
                    endDateUser.value = formatDate(end);
                    fetchFilteredUserShifts();
                }
            });
        }
    });
</script>