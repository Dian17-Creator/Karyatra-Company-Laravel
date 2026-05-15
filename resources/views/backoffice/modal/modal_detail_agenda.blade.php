<div class="modal fade" id="modal_detail_agenda" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Detail Agenda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="detail_id">

                <!-- JUDUL -->
                <div class="mb-3">
                    <label>Judul</label>

                    <input type="text"
                        id="detail_title"
                        class="form-control">
                </div>

                <!-- DESKRIPSI -->
                <div class="mb-3">
                    <label>Deskripsi</label>

                    <textarea id="detail_desc"
                        class="form-control"
                        rows="3"></textarea>
                </div>

                <!-- DATE TIME -->
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">

                    <!-- START DATE -->
                    <div style="flex:2;">
                        <input type="date"
                            id="detail_start_date"
                            class="form-control">
                    </div>

                    <!-- START TIME -->
                    <div style="flex:1;">
                        <input type="time"
                            id="detail_start_time"
                            class="form-control">
                    </div>

                    <!-- TEXT -->
                    <div class="text-center px-2 text-muted fw-semibold">
                        Sampai
                    </div>

                    <!-- END DATE -->
                    <div style="flex:2;">
                        <input type="date"
                            id="detail_end_date"
                            class="form-control">
                    </div>

                    <!-- END TIME -->
                    <div style="flex:1;">
                        <input type="time"
                            id="detail_end_time"
                            class="form-control">
                    </div>

                </div>

                <!-- ALL DAY -->
                <div class="form-check mb-3">

                    <input class="form-check-input"
                        type="checkbox"
                        id="detail_all_day">

                    <label class="form-check-label"
                        for="detail_all_day">

                        All day

                    </label>

                </div>

                <!-- REMINDER -->
                <div class="mb-3">
                    <label class="form-label">Pengingat</label>

                    <select id="detail_reminder_enabled"
                        class="form-select">

                        <option value="0">
                            Nonaktif
                        </option>

                        <option value="1">
                            Aktif
                        </option>

                    </select>
                </div>

                <div id="detail_reminder_container"
                    style="display:none;">

                    <div id="detail_reminder_list"></div>

                    <button type="button"
                        class="btn btn-success w-100 mt-2"
                        id="btnAddDetailReminder">

                        <i class="bi bi-plus-lg"></i>
                        Tambah Pengingat

                    </button>

                </div>

            </div>

            <div class="modal-footer">

                <div class="w-100 d-flex gap-2">

                    <button type="button"
                        class="btn btn-danger flex-fill"
                        id="btnDeleteAgenda">

                        Hapus

                    </button>

                    <button type="button"
                        class="btn btn-success flex-fill"
                        id="btnUpdateAgenda">

                        Simpan

                    </button>

                </div>

            </div>

        </div>
    </div>
</div>