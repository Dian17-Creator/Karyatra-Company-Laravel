@if (auth()->user()->fhrd == 1)
<div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span>Master Departemen</span>
                </div>
                <div class="card-body">
                    <table class="table table-bordered text-center align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Departemen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @isset($departments)
                                @forelse ($departments as $dept)
                                    <tr>
                                        <td>{{ $dept->nid }}</td>
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

                                    {{-- MODAL EDIT & DELETE --}}
                                    @include('backoffice.modal.modal_edit_department', ['dept' => $dept])
                                    @include('backoffice.modal.modal_delete_department', ['dept' => $dept])
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
                </div>
            </div>
        @endif
