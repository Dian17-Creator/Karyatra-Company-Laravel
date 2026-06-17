@if (auth()->user()->fhrd == 1)
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span>Master Departemen</span>

        @if (auth()->user()->fhrd == 1)
        <button class="btn btn-success btn-sm"
            data-bs-target="#addDepartmentModal"
            data-bs-toggle="modal">
            + Tambah Departemen
        </button>
        @endif
    </div>
    <div class="card-body">
        <table class="table table-bordered text-center align-middle">
            <thead class="text-center text-white" style="background:#d7ebff">
                <tr>
                    <th style="background-color: #ffd8e0ff !important;">Nama Departemen</th>
                    <th style="background-color: #ffd8e0ff !important;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @isset($departments)
                @forelse ($departments as $dept)
                <tr>
                    <td>{{ $dept->cname }}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                            data-bs-target="#editDepartmentModal{{ $dept->nid }}">
                            Edit
                        </button>

                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                            data-bs-target="#deleteDepartmentModal{{ $dept->nid }}">
                            Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-muted">Belum ada departemen</td>
                </tr>
                @endforelse
                @else
                <tr>
                    <td colspan="3" class="text-muted">Data departemen tidak tersedia</td>
                </tr>
                @endisset
            </tbody>
        </table>

        {{-- Modals --}}
        @isset($departments)
        @foreach ($departments as $dept)
        @include('backoffice.modal.modal_edit_department', ['dept' => $dept])
        @include('backoffice.modal.modal_delete_department', ['dept' => $dept])
        @endforeach
        @endisset
    </div>
</div>
@endif