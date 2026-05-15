<div class="modal fade" id="addAgendaModal" tabindex="-1" aria-labelledby="addAgendaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="addAgendaForm">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="addAgendaModalLabel">Tambah Agenda</h5>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" name="title" id="agenda_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea name="description" id="agenda_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">

                        <!-- START DATE -->
                        <div style="flex:2;">
                            <input type="date"
                                id="agenda_start_date"
                                class="form-control">
                        </div>

                        <!-- START TIME -->
                        <div style="flex:1;">
                            <input type="time"
                                id="agenda_start_time"
                                class="form-control">
                        </div>

                        <!-- TEXT -->
                        <div class="text-center px-2 text-muted fw-semibold">
                            Sampai
                        </div>

                        <!-- END DATE -->
                        <div style="flex:2;">
                            <input type="date"
                                id="agenda_end_date"
                                class="form-control">
                        </div>

                        <!-- END TIME -->
                        <div style="flex:1;">
                            <input type="time"
                                id="agenda_end_time"
                                class="form-control">
                        </div>

                    </div>

                    <!-- ALL DAY -->
                    <div class="form-check mb-3">

                        <input class="form-check-input"
                            type="checkbox"
                            value="1"
                            id="agenda_all_day"
                            name="is_all_day">

                        <label class="form-check-label"
                            for="agenda_all_day">

                            All day

                        </label>

                    </div>

                    <!-- hidden datetime -->
                    <input type="hidden" name="start_at" id="agenda_start_at">
                    <input type="hidden" name="end_at" id="agenda_end_at">

                    <!-- <hr> -->

                    <div class="mb-3">
                        <label class="form-label">Pengingat</label>

                        <select id="reminder_enabled" class="form-select">
                            <option value="0" selected>Nonaktif</option>
                            <option value="1">Aktif</option>
                        </select>
                    </div>

                    <div id="reminder_container" style="display:none;">

                        <div id="reminder_list"></div>

                        <button type="button"
                            class="btn btn-success w-100 mt-2"
                            id="btnAddReminder">
                            <i class="bi bi-plus-lg"></i>
                            Tambah Pengingat
                        </button>
                    </div>
                </div>

                <div class="modal-footer d-flex gap-2">
                    <button type="button" class="btn btn-danger flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success flex-fill">Simpan Agenda</button>
                </div>
            </div>
        </form>
    </div>
</div>