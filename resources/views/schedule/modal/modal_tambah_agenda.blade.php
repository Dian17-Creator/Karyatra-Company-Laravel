<!-- 🟡 Modal Tambah Agenda -->
<div class="modal fade" id="addAgendaModal" tabindex="-1" aria-labelledby="addAgendaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addAgendaForm">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="addAgendaModalLabel">Tambah
                        Agenda</h5>
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Mulai</label>
                            <input type="date" name="start_at" id="agenda_start_at" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Selesai</label>
                            <input type="date" name="end_at" id="agenda_end_at" class="form-control">
                        </div>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="agenda_all_day"
                            name="is_all_day">
                        <label class="form-check-label" for="agenda_all_day">All day</label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan
                        Agenda</button>
                </div>
            </div>
        </form>
    </div>
</div>
