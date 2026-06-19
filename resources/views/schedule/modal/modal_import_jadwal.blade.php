<div class="modal fade" id="importScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px; overflow: hidden;">

            <form id="importScheduleForm"
                action="{{ route('file-import-schedule') }}"
                method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="modal-header text-white" style="background-color: #0f7643; border-bottom: none; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 18px 24px;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 1.2rem;">
                        Import Jadwal Shift User
                    </h5>
                </div>

                <div class="modal-body" style="padding: 24px;">

                    <div class="upload-area text-center p-4">

                        <img src="{{ asset('images/upload.svg') }}"
                            alt="Upload File"
                            class="upload-icon-img">

                        <input type="file"
                            id="customFile"
                            name="file"
                            accept=".xlsx"
                            hidden
                            required>

                        <button type="button"
                            class="btn-import-select"
                            onclick="document.getElementById('customFile').click()">
                            <i class="bi bi-folder-fill me-2"></i>
                            Pilih File
                        </button>

                        <div id="fileInfo" class="selected-file-card mt-3" style="display:none;">

                            <div class="selected-file-name">
                                <strong id="fileName"></strong>
                            </div>

                            <button type="button" id="removeFile" class="remove-file-btn">
                                <i class="bi bi-x-lg"></i>
                            </button>

                        </div>

                        <div id="emptyFileText" class="mt-3 text-secondary" style="font-size: 0.9rem;">
                            Belum ada file dipilih
                        </div>

                        <div id="dragText" class="mt-1 text-muted" style="font-size: 0.85rem;">
                            atau drag & drop file di sini
                        </div>

                    </div>

                </div>

                <div class="modal-footer d-flex justify-content-between w-100 gap-3" style="background-color: #eef1f4; border-top: none; padding: 18px 24px 22px 24px; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn-import-cancel flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-import-submit flex-fill">Simpan</button>
                </div>

            </form>

        </div>
    </div>
</div>

<style>
    .upload-area {
        border: 2px dashed #0f7643;
        border-radius: 16px;
        background: #fafcfb;
        transition: 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 250px;
    }

    .upload-icon-img {
        width: 55px;
        height: auto;
        margin-bottom: 20px;
    }

    .upload-area:hover,
    .upload-area.drag-over {
        background: #f1f8f3;
        border-color: #157347;
    }

    .btn-import-select {
        background-color: #0f7643;
        color: #fff;
        border: 1px solid #0f7643;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 1rem;
        font-weight: 500;
        transition: 0.2s;
        box-shadow: 0 2px 4px rgba(15, 118, 67, 0.2);
    }

    .btn-import-select:hover {
        background-color: #157347;
        border-color: #157347;
        color: #fff;
    }

    .btn-import-cancel {
        background-color: #4e545c;
        color: #fff;
        border: 1px solid #4e545c;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn-import-cancel:hover {
        background-color: #3f444a;
        border-color: #3f444a;
        color: #fff;
    }

    .btn-import-submit {
        background-color: #0f7643;
        color: #fff;
        border: 1px solid #0f7643;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn-import-submit:hover {
        background-color: #157347;
        border-color: #157347;
        color: #fff;
    }

    .selected-file-card {
        width: fit-content;
        max-width: 90%;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .04);
        margin: 0 auto;
    }

    .selected-file-name {
        display: flex;
        align-items: center;
        font-size: 14px;
        font-weight: bold;
        color: #212529;
        white-space: nowrap;
    }

    .remove-file-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #dc3545;
        background: #fff;
        color: #dc3545;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        transition: .2s;
        flex-shrink: 0;
    }

    .remove-file-btn:hover {
        background: #dc3545;
        color: #fff;
    }
</style>

<script>
    (function() {
        const fileInput = document.getElementById('customFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const dragText = document.getElementById('dragText');
        const emptyFileText = document.getElementById('emptyFileText');
        const removeFile = document.getElementById('removeFile');
        const uploadArea = document.querySelector('.upload-area');

        // File input change handler
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                fileInfo.style.display = 'flex';
                if (dragText) dragText.style.display = 'none';
                if (emptyFileText) emptyFileText.style.display = 'none';
            } else {
                fileInfo.style.display = 'none';
                if (dragText) dragText.style.display = 'block';
                if (emptyFileText) emptyFileText.style.display = 'block';
            }
        });

        // Remove file handler
        removeFile.addEventListener('click', function() {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            if (dragText) dragText.style.display = 'block';
            if (emptyFileText) emptyFileText.style.display = 'block';
        });

        // Drag & drop handlers
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.classList.add('drag-over');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.classList.remove('drag-over');
            }, false);
        });

        uploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                fileInput.files = files;
                // Trigger change event manually
                const event = new Event('change', {
                    bubbles: true
                });
                fileInput.dispatchEvent(event);
            }
        });
    })();
</script>