{{-- Modal Filter Departemen --}}
<div class="modal fade" id="deptFilterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <input type="hidden" id="deptId" value="">

            <div class="modal-header">
                <h5 class="modal-title">Departemen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <div class="list-group list-group-flush">

                    {{-- Semua --}}
                    <button class="list-group-item list-group-item-action"
                        onclick="selectDepartment('', 'Semua Departemen')">
                        Semua Departemen
                    </button>

                    {{-- Data dari DB --}}
                    @foreach ($departments as $dept)
                        <button class="list-group-item list-group-item-action"
                            onclick="selectDepartment('{{ $dept->nid }}', '{{ $dept->cname }}')">
                            {{ $dept->cname }}
                        </button>
                    @endforeach

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary flex-fill py-2" onclick="resetDeptFilter()">
                    Default
                </button>

                <button class="btn btn-success flex-fill py-2" data-bs-dismiss="modal" onclick="applyDeptFilter()">
                    Terapkan
                </button>
            </div>

        </div>
    </div>
</div>
