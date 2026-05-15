{{-- resources/views/penggajian/components/table_rekening.blade.php --}}

<div class="card mt-3 payroll-card" style="margin-bottom: 20px">

    <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
        <h5 class="mb-0">Master Rekening</h5>
    </div>

    <div class="card-body p-2">
        <div class="table-scroll">
            <table class="table table-bordered table-sm">
                <thead class="text-center" style="background:#d7ffe2">
                    <tr>
                        <th>No</th>
                        <th>No Rekening</th>
                        <th>Bank</th>
                        <th>Atas Nama</th>
                        <th>Cabang</th>
                        <th style="width: 100px;">Action</th>
                    </tr>
                </thead>

                <tbody class="text-center">
                    @forelse($mrekening as $r)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $r->nomor_rekening }}</td>
                            <td>{{ $r->bank }}</td>
                            <td>{{ $r->atas_nama }}</td>
                            <td>{{ $r->cabang }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit-rekening" data-id="{{ $r->id }}"
                                    data-nomor="{{ $r->nomor_rekening }}" data-bank="{{ $r->bank }}"
                                    data-atas="{{ $r->atas_nama }}" data-cabang="{{ $r->cabang }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditRekening">
                                    Edit
                                </button>

                                <form action="{{ route('mrekening.destroy', $r->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Hapus rekening ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted">Belum ada data rekening</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
