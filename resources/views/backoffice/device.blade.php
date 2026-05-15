{{-- ====== TABEL DEVICE ADMIN ====== --}}
@if (auth()->user()->fhrd == 1)
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span>Manajemen Device Admin</span>
        </div>

        <div class="card-body">
            <table class="table table-bordered text-center align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Admin</th>
                        <th>Device ID</th>
                        <th>Approval</th>
                        <th>Status</th>
                        <th>Terakhir Digunakan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>{{ $device->id }}</td>
                            <td>{{ $device->user->cname ?? '-' }}</td>

                            <td class="text-break" style="max-width:220px">
                                {{ $device->device_id }}
                            </td>

                            {{-- APPROVAL STATUS --}}
                            <td>
                                @if ($device->approval_status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif ($device->approval_status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>

                            {{-- ACTIVE STATUS --}}
                            <td>
                                @if ($device->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                            <td>
                                {{ $device->last_used_at?->format('d M Y H:i') ?? '-' }}
                            </td>

                            {{-- AKSI --}}
                            <td class="d-flex justify-content-center gap-1 flex-wrap">

                                {{-- APPROVE / REJECT --}}
                                @if ($device->approval_status === 'pending')
                                    <form action="{{ route('admin-devices.approve', $device->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-success btn-sm">
                                            Approve
                                        </button>
                                    </form>

                                    <form action="{{ route('admin-devices.reject', $device->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-danger btn-sm">
                                            Reject
                                        </button>
                                    </form>
                                @endif

                                {{-- TOGGLE (HANYA JIKA APPROVED) --}}
                                @if ($device->approval_status === 'approved')
                                    <form action="{{ route('admin-devices.toggle', $device->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-warning btn-sm">
                                            {{ $device->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endif

                                {{-- DELETE --}}
                                <form action="{{ route('admin-devices.destroy', $device->id) }}" method="POST"
                                    onsubmit="return confirm('Yakin hapus device ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">
                                Belum ada device terdaftar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
