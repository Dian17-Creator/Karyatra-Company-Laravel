{{-- resources/views/penggajian/components/table_payroll.blade.php --}}

@php
    if (!function_exists('rpOrDash')) {
        function rpOrDash($value)
        {
            if ($value === null || $value === '') {
                return '-';
            }

            // Jika sudah string (misal "Rp 0,00")
            if (is_string($value)) {
                // cek apakah ada angka selain nol
                return preg_match('/[1-9]/', $value) ? $value : '-';
            }

            // Numeric
            return (float) $value === 0.0 ? '-' : 'Rp ' . number_format((float) $value, 2, ',', '.');
        }
    }
@endphp


<div class="card mt-3 payroll-card" style="margin-bottom: 30px">

    <div class="card-header bg-danger text-white">
        <h4 class="mb-0">Data Penggajian</h4>
    </div>

    <div class="card-body">
        <div class="table-scroll table-sticky">

            <table class="table table-bordered table-striped table-sm table-payroll">
                <thead class="text-center">
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">
                            <label class="d-flex align-items-center justify-content-center gap-1 m-0">
                                Pilih
                                <input type="checkbox" id="select_all" style="margin-left:6px;">
                            </label>
                        </th>
                        <th rowspan="2">Nama</th>
                        <th rowspan="2">Jabatan</th>
                        <th rowspan="2">Jumlah Masuk</th>
                        <th rowspan="2">Gaji</th>
                        <th rowspan="2">Gaji Pokok</th>

                        <th colspan="6" class="group-header">Tunjangan</th>

                        <th rowspan="2">Gaji Lembur</th>
                        <th rowspan="2">Bonus Kehadiran</th>
                        <th rowspan="2">Tabungan Diambil</th>

                        <th colspan="3" class="group-header">Potongan</th>

                        <th rowspan="2">Total</th>
                        <th rowspan="2">Note</th>
                        <th rowspan="2">Ket Absensi</th>
                        <th rowspan="2">Alasan Edit</th>
                        <th rowspan="2">Status</th>

                        <th rowspan="2">Email</th>
                        <th rowspan="2">Keterangan User</th>
                        <th rowspan="2">Action</th>
                        <th rowspan="2">Resend Email</th>
                        <th rowspan="2">Slip Gaji</th>
                    </tr>

                    <tr>
                        <th>Makan</th>
                        <th>Jabatan</th>
                        <th>Transport</th>
                        <th>Luar Kota</th>
                        <th>Masa Kerja</th>
                        <th>Backup</th>

                        <th>Lain</th>
                        <th>Tabungan</th>
                        <th>Keterlambatan</th>
                    </tr>
                </thead>

                <tbody id="tablePayrollBody" class="text-center">
                    @php
                        $selectedDep = (string) request()->query('department_id', '');
                    @endphp

                    @forelse($data as $row)
                        @php
                            $r = is_object($row) ? (array) $row : $row;
                            $rowDep = (string) ($r['department_id'] ?? '');
                        @endphp

                        {{-- jika ada filter department yang valid dan row tidak cocok -> skip --}}
                        @if ($selectedDep !== '' && $rowDep !== $selectedDep)
                            @continue
                        @endif

                        <tr data-user-id="{{ $r['user_id'] ?? '' }}"
                            data-department-id="{{ $r['department_id'] ?? '' }}"
                            data-period-month="{{ $month ?? ($selMonth ?? \Carbon\Carbon::now()->month) }}"
                            data-period-year="{{ $year ?? ($selYear ?? \Carbon\Carbon::now()->year) }}">
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <input type="checkbox" class="payroll-row-checkbox" value="{{ $r['id'] }}"
                                    data-name="{{ $r['user_name'] }}" data-jabatan="{{ $r['jabatan'] }}"
                                    data-hari="{{ $r['jumlah_masuk'] }}">
                            </td>

                            <td>{{ $r['user_name'] }}</td>
                            <td>{{ $r['jabatan'] }}</td>
                            <td>{{ $r['jumlah_masuk'] }}</td>

                            <td>{{ rpOrDash($r['gaji'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['gaji_pokok'] ?? null) }}</td>

                            <td>{{ rpOrDash($r['tunjangan_makan'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['tunjangan_jabatan'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['tunjangan_transport'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['tunjangan_luar_kota'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['tunjangan_masa_kerja'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['tunjangan_backup'] ?? null) }}</td>

                            <td>{{ rpOrDash($r['gaji_lembur'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['bonus_kehadiran'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['tabungan_diambil'] ?? null) }}</td>

                            <td>{{ rpOrDash($r['potongan_lain'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['potongan_tabungan'] ?? null) }}</td>
                            <td>{{ rpOrDash($r['potongan_keterlambatan'] ?? null) }}</td>

                            <td class="fw-semibold">{{ rpOrDash($r['total_gaji'] ?? null) }}</td>

                            <td>{{ $r['note'] ?? '-' }}</td>

                            <td class="ket-absensi-cell">{!! e($r['keterangan_absensi'] ?? 'A = 0, I = 0, S = 0') !!}</td>

                            <td>{{ $r['reasonedit'] ?? '-' }}</td>

                            <td>
                                @php $s = strtoupper($r['status'] ?? '-') @endphp

                                @if ($s === 'APPROVED')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($s === 'REJECTED')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($s === 'PENDING')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @else
                                    {{ $s }}
                                @endif
                            </td>

                            <td>
                                @php
                                    $emailStatus = strtoupper($r['email_status'] ?? 'PENDING');
                                @endphp

                                @if ($emailStatus === 'SENT')
                                    <span class="badge bg-success">Sent</span>
                                @elseif ($emailStatus === 'FAILED')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>

                            <td>{{ $r['user_note'] ?? '-' }}</td>

                            <td>
                                @if (strtoupper($r['status'] ?? '') !== 'APPROVED')
                                    <button class="btn btn-sm btn-warning btn-open-edit"
                                        data-id="{{ e($r['id'] ?? '') }}" data-user-id="{{ e($r['user_id'] ?? '') }}"
                                        data-jumlah-masuk="{{ e($r['jumlah_masuk'] ?? 0) }}"
                                        data-gaji-harian="{{ e($r['gaji_harian'] ?? 0) }}"
                                        data-gaji-pokok="{{ e($r['gaji_pokok'] ?? 0) }}"
                                        data-tunjangan-makan="{{ e($r['tunjangan_makan'] ?? 0) }}"
                                        data-tunjangan-jabatan="{{ e($r['tunjangan_jabatan'] ?? 0) }}"
                                        data-tunjangan-transport="{{ e($r['tunjangan_transport'] ?? 0) }}"
                                        data-tunjangan-luar-kota="{{ e($r['tunjangan_luar_kota'] ?? 0) }}"
                                        data-tunjangan-masa-kerja="{{ e($r['tunjangan_masa_kerja'] ?? 0) }}"
                                        data-tunjangan-backup="{{ e($r['tunjangan_backup'] ?? 0) }}"
                                        data-gaji-lembur="{{ e($r['gaji_lembur'] ?? 0) }}"
                                        data-bonus-kehadiran="{{ e($r['bonus_kehadiran_rp'] ?? 0) }}"
                                        data-tabungan-diambil="{{ e($r['tabungan_diambil'] ?? 0) }}"
                                        data-potongan-lain="{{ e($r['potongan_lain'] ?? 0) }}"
                                        data-potongan-tabungan="{{ e($r['potongan_tabungan'] ?? 0) }}"
                                        data-potongan-keterlambatan="{{ e($r['potongan_keterlambatan'] ?? 0) }}"
                                        data-note="{{ e($r['note'] ?? '') }}"
                                        data-reasonedit="{{ e($r['reasonedit'] ?? '') }}" data-bs-toggle="modal"
                                        data-bs-target="#modalEditPayroll">
                                        Edit
                                    </button>
                                @endif
                            </td>

                            <td>
                                @if (in_array($emailStatus, ['FAILED', 'PENDING']))
                                    <form method="POST"
                                        action="{{ route('salary.resend', [$r['user_id'], $year, $month]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-warning">
                                            🔁 Resend
                                        </button>
                                    </form>
                                @else
                                    <span class="text-success">✓ Sent</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if (!empty($r['pdf_url']))
                                    <a href="{{ $r['pdf_url'] }}" target="_blank" class="btn btn-sm btn-primary">
                                        📄 PDF
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="24" class="text-muted">Data belum tersedia</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

            @push('scripts')
                <script>
                    function applyRecalcResults(results = []) {
                        if (!Array.isArray(results)) return;

                        results.forEach(item => {
                            const userId = item.user_id ?? item.userId ?? item.nuserid;
                            const ket = item.ket ?? item.keterangan_absensi ?? item.keterangan ?? '';
                            if (!userId) return;
                            const row = document.querySelector('tr[data-user-id="' + userId + '"]');
                            if (!row) return;
                            const cell = row.querySelector('.ket-absensi-cell');
                            if (!cell) return;
                            cell.textContent = ket && ket.trim().length ? ket : 'A = 0, I = 0, S = 0';
                        });
                    }
                </script>
            @endpush
        </div>
    </div>
</div>

<script src="{{ asset('js/penggajian.js') }}"></script>
