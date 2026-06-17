<div class="card mb-4 shadow-sm" id="masterUserContainer">
    <div class="card-header text-white bg-danger">
        Daftar User
    </div>
    <div class="card-body">
        <div class="table-scroll">
            <table id="userTable" class="table table-bordered align-middle text-center table-users">
                <thead class="bg-light">
                    <tr>
                        <th style="background-color: #ffd8e0ff !important;">Username</th>
                        <th style="background-color: #ffd8e0ff !important;">Gmail</th>
                        <th style="background-color: #ffd8e0ff !important;">No Telepon</th>
                        <th style="background-color: #ffd8e0ff !important;">Nomor KTP</th>
                        <th style="background-color: #ffd8e0ff !important;">Nomor Rekening</th>
                        <th style="background-color: #ffd8e0ff !important;">Jenis Bank</th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="nama">
                            Nama <span class="sort-icon" id="sortIconNama">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="fullname">
                            Nama Lengkap <span class="sort-icon" id="sortIconFullname">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="finger">
                            Finger ID <span class="sort-icon" id="sortIconFinger">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="tanggal">
                            Tanggal Masuk <span class="sort-icon" id="sortIconTanggal">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="cabang">
                            Department <span class="sort-icon" id="sortIconCabang">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="payroll">
                            Payroll Department <span class="sort-icon" id="sortIconPayroll">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;" class="sortable" data-column="role">
                            Role <span class="sort-icon" id="sortIconRole">↕</span>
                        </th>
                        <th style="background-color: #ffd8e0ff !important;">Status</th>
                        <th style="background-color: #ffd8e0ff !important;">Notifikasi</th>
                        <th style="background-color: #ffd8e0ff !important;">Absensi</th>
                        <th style="background-color: #ffd8e0ff !important;">Izin</th>
                        <th style="background-color: #ffd8e0ff !important;">Face</th>
                        <th style="background-color: #ffd8e0ff !important;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($users)
                    @forelse($users as $user)
                    <tr data-status="{{ $user->factive ? '1' : '0' }}">

                        <td>{{ $user->cemail }}</td>
                        <td>{{ $user->cmailaddress ?? '-' }}</td>
                        <td>{{ $user->cphone ?? '-' }}</td>
                        <td>{{ $user->cktp ?? '-' }}</td>

                        <td>
                            {{ $user->caccnumber ? preg_replace('/\s+/', '', $user->caccnumber) : $user->rekening->nomor_rekening ?? '-' }}
                        </td>

                        <td>
                            @php
                            $bankUser = trim((string) ($user->bank ?? ''));
                            $bankRel = trim((string) ($user->rekening->bank ?? ''));
                            $bankName = $bankUser !== '' ? $bankUser : ($bankRel !== '' ? $bankRel : '');
                            $atasNama = trim((string) ($user->rekening->atas_nama ?? ''));
                            @endphp

                            @if ($bankName === '')
                            -
                            @elseif (strtolower($bankName) === 'mandiri')
                            {{ $atasNama !== '' ? 'BANK MANDIRI - ' . $atasNama : 'BANK MANDIRI' }}
                            @else
                            {{ strtoupper($bankName) }}
                            @endif
                        </td>

                        <td>{{ $user->cname }}</td>
                        <td>{{ $user->cfullname ?? '-' }}</td>
                        <td>{{ $user->finger_id ?? '-' }}</td>
                        <td>{{ $user->dtanggalmasuk ? \Carbon\Carbon::parse($user->dtanggalmasuk)->format('d M Y') : '-' }}
                        </td>
                        <td>{{ $user->department->cname ?? '-' }}</td>
                        <td>{{ $user->payrollDepartment->cname ?? '-' }}</td>
                        <td>
                            @if ($user->fhrd)
                            <span class="badge bg-info text-dark">HRD</span>
                            @elseif ($user->fsuper)
                            <span class="badge bg-primary">Supervisor</span>
                            @elseif ($user->fadmin)
                            <span class="badge bg-success">Captain</span>
                            @elseif ($user->fsenior)
                            <span class="badge bg-warning text-dark">Senior Crew</span>
                            @else
                            <span class="badge bg-secondary">Crew</span>
                            @endif
                        </td>

                        <td>
                            @if ($user->isActive())
                            <span class="badge bg-success">Aktif</span>
                            @else
                            <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>

                        <td>
                            @if ($user->fnotif)
                            <span class="badge bg-success">Aktif</span>
                            @else
                            <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('backoffice.viewLogs', $user->nid) }}"
                                class="btn btn-info btn-sm text-white">Lihat</a>
                            @if (auth()->user()->fhrd == 1)
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#deleteLogsModal{{ $user->nid }}">
                                Hapus
                            </button>
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('backoffice.viewRequests', $user->nid) }}"
                                class="btn btn-info btn-sm text-white">Lihat</a>

                            @if (auth()->user()->fhrd == 1)
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#deleteLogsModal{{ $user->nid }}">
                                Hapus
                            </button>
                            @endif
                        </td>

                        <td>
                            @if ($user->faces->isNotEmpty())
                            <button class="btn btn-outline-primary btn-sm" title="Lihat Face User"
                                onclick="window.location.href='{{ route('hr.face_approval.show', $user->nid) }}'">
                                Lihat
                            </button>
                            @else
                            <span class="badge bg-danger">Belum</span>
                            @endif
                        </td>

                        <td>
                            <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal"
                                data-bs-target="#editUserModal{{ $user->nid }}">
                                Edit
                            </button>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="15" class="text-center text-muted">
                            Belum ada data user
                        </td>
                    </tr>
                    @endforelse
                    @else
                    <tr>
                        <td colspan="15" class="text-center text-muted">
                            Data user tidak tersedia
                        </td>
                    </tr>
                    @endisset
                </tbody>
            </table>
        </div>

        {{-- Modals --}}
        @isset($users)
        @foreach($users as $user)
        @include('backoffice.modal.modal_edit_user', [
        'user' => $user,
        'departments' => $departments ?? collect(),
        'rekenings' => $rekenings ?? collect(),
        ])

        <div class="modal fade" id="deleteLogsModal{{ $user->nid }}" tabindex="-1"
            aria-labelledby="deleteLogsModalLabel{{ $user->nid }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteLogsModalLabel{{ $user->nid }}">
                            Konfimasi Hapus Logs User {{ $user->cname }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p>Apakah Anda Ingin Menghapus Data Ini?</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <form method="POST" action="{{ route('backoffice.deleteLogs') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->nid }}">
                            <button type="submit" class="btn btn-danger">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteRequestsModal{{ $user->nid }}" tabindex="-1"
            aria-labelledby="deleteRequestsModalLabel{{ $user->nid }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteRequestsModalLabel{{ $user->nid }}">
                            Konfimasi Hapus Requests User {{ $user->cname }} </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p>Apakah Anda Ingin Menghapus Data Ini?</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <form method="POST" action="{{ route('backoffice.deleteRequests') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->nid }}">
                            <button type="submit" class="btn btn-danger">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @endisset

        <div class="mt-3 px-2">
            {{ $users->withQueryString()->links('penggajian.components.custom_pagination') }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.userPaginationBound) return;
        window.userPaginationBound = true;

        document.addEventListener('click', async function(e) {
            const paginationLink = e.target.closest('#masterUserContainer .custom-page-link');

            if (paginationLink && paginationLink.tagName.toLowerCase() === 'a') {
                e.preventDefault();

                const container = document.getElementById('masterUserContainer');
                if (!container) return;

                // Efek loading transparan
                container.style.transition = 'opacity 0.2s';
                container.style.opacity = '0.5';
                container.style.pointerEvents = 'none';

                try {
                    const response = await fetch(paginationLink.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Component': 'master_user'
                        }
                    });
                    const html = await response.text();

                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('masterUserContainer');

                    if (newContent) {
                        container.innerHTML = newContent.innerHTML;
                    }

                    // Update URL browser tanpa refresh
                    window.history.pushState({}, '', paginationLink.href);

                } catch (error) {
                    console.error('Error fetching pagination:', error);
                } finally {
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                }
            }
        });

        // Delegasi Event untuk Modal Edit User (Bank Mandiri toggle)
        document.addEventListener('show.bs.modal', function(event) {
            const modal = event.target;
            if (modal && modal.id && modal.id.startsWith('editUserModal')) {
                const nid = modal.id.replace('editUserModal', '');
                const bankSelect = document.getElementById('bankSelect' + nid);
                const mandiriWrapper = document.getElementById('mandiriRekeningWrapper' + nid);
                const rekeningSelect = document.getElementById('rekeningSelect' + nid);

                function isMandiri(value) {
                    return String(value || '').toLowerCase() === 'mandiri';
                }

                function toggleMandiriDropdown() {
                    if (!bankSelect) return;
                    if (isMandiri(bankSelect.value)) {
                        if (mandiriWrapper) mandiriWrapper.style.display = '';
                        if (rekeningSelect) rekeningSelect.setAttribute('required', 'required');
                    } else {
                        if (mandiriWrapper) mandiriWrapper.style.display = 'none';
                        if (rekeningSelect) rekeningSelect.removeAttribute('required');
                    }
                }

                // Jalankan inisialisasi awal saat modal dibuka
                toggleMandiriDropdown();

                // Daftarkan event change jika belum terdaftar
                if (bankSelect && !bankSelect.dataset.listenerAdded) {
                    bankSelect.addEventListener('change', toggleMandiriDropdown);
                    bankSelect.dataset.listenerAdded = 'true';
                }
            }
        });
    });
</script>