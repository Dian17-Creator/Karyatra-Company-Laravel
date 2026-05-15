<div class="modal fade" id="deleteLogsModal{{ $user->nid }}" tabindex="-1"
    aria-labelledby="deleteLogsModalLabel{{ $user->nid }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteLogsModalLabel{{ $user->nid }}">Konfimasi Hapus Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p>Apakah Anda Ingin Menghapus Data Ini?</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batak
                </button>

                <form method="POST" action="{{ route('backoffice.deleteLogs') }}">
                    @csrf
                    <input type="hidden" name="nid" value="{{ $user->nid }}">
                    <button type="submit" class="btn btn-danger">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
