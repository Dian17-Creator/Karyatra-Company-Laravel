<div class="card mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span>Daftar Kontrak Kerja Karyawan</span>
        @if (auth()->user()->fhrd == 1)
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addContractModal">
            + Tambah Kontrak
        </button>
        @endif
    </div>

    <div class="card-body">
        <table class="table table-bordered align-middle text-center">
            <thead class="table-success">
                <tr>
                    <th style="background-color: #ffd8e0ff !important;">Nama Pegawai</th>
                    <th style="background-color: #ffd8e0ff !important;">Tanggal Mulai</th>
                    <th style="background-color: #ffd8e0ff !important;">Tanggal Akhir</th>
                    <th style="background-color: #ffd8e0ff !important;">Tipe Kontrak</th>
                    <th style="background-color: #ffd8e0ff !important;">Status</th>
                    <th style="background-color: #ffd8e0ff !important;">Sisa Hari</th>
                    <th style="background-color: #ffd8e0ff !important;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contracts as $contract)
                <tr>
                    {{-- Nama Pegawai --}}
                    <td>{{ $contract->user->cname ?? '-' }}</td>

                    {{-- Tanggal Mulai --}}
                    <td>
                        {{ $contract->dstart ? \Carbon\Carbon::parse($contract->dstart)->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Tanggal Akhir --}}
                    <td>
                        {{ $contract->dend ? \Carbon\Carbon::parse($contract->dend)->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Tipe Kontrak --}}
                    <td>
                        @if ($contract->ctermtype === 'probation')
                        <span class="badge bg-warning text-dark">Probation</span>
                        @elseif ($contract->ctermtype === 'promotion')
                        <span class="badge bg-info text-dark">Promotion</span>
                        @else
                        <span class="badge bg-secondary">Evaluation</span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td>
                        @if ($contract->cstatus === 'active')
                        <span class="badge bg-success">Aktif</span>
                        @else
                        <span class="badge bg-danger">Dihentikan</span>
                        @endif
                    </td>

                    {{-- Sisa Hari --}}
                    <td>
                        @php
                        // pastikan remaining_days ada; kalau tidak, hitung dari dend
                        if (isset($contract->remaining_days)) {
                        $remaining = floor($contract->remaining_days);
                        } else {
                        $remaining = null;
                        if ($contract->dend) {
                        $remaining = \Carbon\Carbon::parse($contract->dend)->diffInDays(
                        \Carbon\Carbon::now(),
                        false,
                        );
                        }
                        }
                        @endphp

                        @if (!is_null($remaining) && $remaining > 0)
                        <span class="text-success fw-bold">{{ $remaining }} hari lagi</span>
                        @elseif ($remaining === 0)
                        <span class="text-warning fw-bold">Habis hari ini</span>
                        @else
                        <span class="text-danger fw-bold">Sudah Habis</span>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td>
                        @if (auth()->user()->fhrd == 1)
                        <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal"
                            data-bs-target="#editContractModal{{ $contract->nid }}">
                            Edit
                        </button>

                        <form action="{{ route('schedule.contract.destroy', $contract->nid) }}" method="POST"
                            class="d-inline" onsubmit="return confirm('Yakin ingin menghapus kontrak ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>

                {{-- Modal Edit Kontrak --}}
                @include('schedule.modal.modal_edit_kontrak')
                @empty
                <tr>
                    <td colspan="8" class="text-muted">Belum ada data kontrak kerja</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>