{{-- Modal Filter Tanggal --}}
<div class="modal fade" id="dateFilterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Tanggal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="list-group mb-3">
                    <button class="list-group-item list-group-item-action" onclick="setRange('today')">Hari ini</button>
                    <button class="list-group-item list-group-item-action"
                        onclick="setRange('yesterday')">Kemarin</button>
                    <button class="list-group-item list-group-item-action" onclick="setRange('7days')">7 Hari yang
                        lalu</button>
                    <button class="list-group-item list-group-item-action" onclick="setRange('30days')">30 Hari yang
                        lalu</button>
                    <button class="list-group-item list-group-item-action" onclick="setRange('thisMonth')">Bulan
                        ini</button>
                    <button class="list-group-item list-group-item-action" onclick="setRange('lastMonth')">Bulan
                        kemarin</button>
                </div>

                <div class="row g-2 align-items-end">

                    <div class="col">
                        <label>Dari</label>
                        <input type="date" id="startDate" class="form-control">
                    </div>

                    <div class="col-auto">
                        <div class="date-sync-icon" onclick="syncDate()" title="Samakan tanggal">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                    </div>

                    <div class="col">
                        <label>Sampai</label>
                        <input type="date" id="endDate" class="form-control">
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <div class="d-flex w-100 gap-3">
                    <button class="btn btn-outline-secondary flex-fill py-2" onclick="setRange('today')">
                        Default
                    </button>

                    <button class="btn btn-success flex-fill py-2" data-bs-dismiss="modal" onclick="applyFilter()">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .date-sync-icon {
        font-size: 18px;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
    }

    .date-sync-icon:hover {
        color: #00770c;
        transform: scale(1.15);
    }
</style>
