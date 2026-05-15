{{-- resources/views/penggajian/components/table_tunjangan.blade.php --}}

@php
    function rupiahOrDash($value)
    {
        if ($value === null || (float) $value == 0) {
            return '-';
        }
        return 'Rp ' . number_format((float) $value, 2, ',', '.');
    }
@endphp

<div class="card mt-3 payroll-card" style="margin-bottom: 20px">

    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Gaji dan Tunjangan</h5>
    </div>

    <div class="card-body p-2">
        <div class="table-scroll">
            <table class="table table-bordered table-sm">
                <thead class="text-center" style="background:#d7ebff">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Tgl Berlaku</th>
                        <th>Jenis Gaji</th>
                        <th>Nominal</th>
                        <th>Makan</th>
                        <th>Jabatan</th>
                        <th>Transport</th>
                        <th>Luar Kota</th>
                        <th>Masa Kerja</th>
                        <th>Backup</th>
                    </tr>
                </thead>

                <tbody class="text-center">
                    @forelse($mtunjangan as $t)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $t->user->cname ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($t->tanggal_berlaku)->format('d-m-Y') }}</td>
                            <td>{{ $t->jenis_gaji }}</td>
                            {{-- <td>Rp {{ number_format($t->nominal_gaji, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($t->tunjangan_makan, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($t->tunjangan_jabatan, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($t->tunjangan_transport, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($t->tunjangan_luar_kota, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($t->tunjangan_masa_kerja, 2, ',', '.') }}</td> --}}
                            <td>{{ rupiahOrDash($t->nominal_gaji) }}</td>
                            <td>{{ rupiahOrDash($t->tunjangan_makan) }}</td>
                            <td>{{ rupiahOrDash($t->tunjangan_jabatan) }}</td>
                            <td>{{ rupiahOrDash($t->tunjangan_transport) }}</td>
                            <td>{{ rupiahOrDash($t->tunjangan_luar_kota) }}</td>
                            <td>{{ rupiahOrDash($t->tunjangan_masa_kerja) }}</td>
                            {{-- <td>Rp {{ number_format($t->tunjangan_backup, 2, ',', '.') }}</td> --}}
                            <td>{{ rupiahOrDash($t->tunjangan_backup) }}</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-muted">Belum ada data tunjangan</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>
