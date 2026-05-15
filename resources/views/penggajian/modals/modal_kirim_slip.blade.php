{{-- resources/views/penggajian/model_kirim_slip.blade.php --}}
<div class="modal fade" id="modalKirimSlip" tabindex="-1" aria-labelledby="modalKirimSlipLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <form id="formKirimSlip" method="post" action="{{ route('gaji.kirim') }}">
                @csrf

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Kirim Slip Gaji — Preview & Konfirmasi</h5>
                </div>

                <div class="modal-body">

                    <!-- ================= TOP BAR: TOGGLE EMAIL/WA + PREVIEW BUTTON ================= -->
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <!-- TOGGLE KIRI -->
                        <div class="d-flex gap-4 align-items-center">

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="toggleWa">
                                <label class="form-check-label" for="toggleWa">Kirim via WhatsApp</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="chkSendEmail" checked>
                                <label class="form-check-label" for="chkSendEmail">Kirim via Email</label>
                            </div>

                        </div>

                        <!-- BUTTON PREVIEW KANAN -->
                        <div class="d-flex gap-2">
                            <button type="button" id="btnPreviewPdf" class="btn btn-sm btn-primary">
                                Preview PDF
                            </button>

                            <button type="button" id="btnPreviewWa" class="btn btn-sm btn-outline-primary">
                                Preview WhatsApp
                            </button>
                        </div>

                    </div>
                    <!-- ========================================================================== -->

                    <div class="row">
                        <!-- KIRI: LIST USER -->
                        <div class="col-4">
                            <div class="list-group" id="previewList" style="max-height:420px; overflow:auto;">
                                {{-- item list-group akan muncul via JS --}}
                            </div>
                        </div>

                        <!-- KANAN: AREA PREVIEW -->
                        <div class="col-8">
                            <div class="card">
                                <div class="card-body" style="min-height:400px">

                                    <!-- PREVIEW PDF -->
                                    <div id="emailPreview">
                                        <iframe id="slipFrame" src=""
                                            style="width:100%; height:380px; border:none;"></iframe>
                                    </div>

                                    <!-- PREVIEW WA -->
                                    <div id="waPreview" style="display:none;">

                                        <div id="waTextPreview"
                                            style="white-space: pre-line;
                                                    background:#e8fddc;
                                                    border:1px solid #c5e6b3;
                                                    padding:12px;
                                                    border-radius:10px;
                                                    margin-bottom:10px;
                                                    font-size:14px;">
                                        </div>

                                        <iframe id="waSlipFrame" src=""
                                            style="width:100%; height:310px; border:none;">
                                        </iframe>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="selected_ids" id="selected_ids" value="[]">
                    <!-- hidden field untuk toggle WA -->
                    <input type="hidden" name="send_wa" id="send_wa" value="0">

                </div>

                <!-- FOOTER -->
                <div class="modal-footer d-flex flex-column">

                    <!-- PROGRESS AREA -->
                    <div id="sendingStatusWrap" style="width:100%; display:none; margin-bottom:10px">
                        <div class="d-flex justify-content-between mb-1">
                            <strong id="progressLabel">Mengirim 0 / 0</strong>
                            <small id="progressSummary">
                                Sukses: <span id="countSuccess">0</span> •
                                Gagal: <span id="countFailed">0</span>
                            </small>
                        </div>
                        <div style="height:10px; background:#eee; border-radius:6px; overflow:hidden">
                            <div id="progressInner"
                                style="width:0%; height:100%; transition:width .3s; background:#0d6efd"></div>
                        </div>
                        <div id="sendingLog" style="max-height:120px; overflow:auto; margin-top:8px; font-size:13px">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end w-100 gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success" id="btnSendSlip">Kirim Slip Terpilih</button>
                    </div>

                </div>

            </form>
        </div>
    </div>
</div>


{{-- Script: jalankan di bawah include modal (atau di bagian <script> halaman) --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ============================================================
           ELEMENTS
        ============================================================ */
        const modal = document.getElementById('modalKirimSlip');

        const previewList = document.getElementById('previewList');
        const previewContent = document.getElementById('previewContent');
        const selectedIdsInput = document.getElementById('selected_ids');
        const formKirim = document.getElementById('formKirimSlip');
        const btnSendSlip = document.getElementById('btnSendSlip');
        const chkSendEmail = document.getElementById('chkSendEmail');

        // NEW — toggle WA + preview buttons + preview areas
        const toggleWa = document.getElementById('toggleWa');
        const sendWaHidden = document.getElementById('send_wa');
        const btnPreviewPdf = document.getElementById('btnPreviewPdf');
        const btnPreviewWa = document.getElementById('btnPreviewWa');
        const emailPreview = document.getElementById('emailPreview');
        const waPreview = document.getElementById('waPreview');
        const waTextPreview = document.getElementById('waTextPreview');
        const waSlipFrame = document.getElementById('waSlipFrame');

        /* ============================================================
           INITIAL PREVIEW STATE
        ============================================================ */
        if (emailPreview) emailPreview.style.display = "block";
        if (waPreview) waPreview.style.display = "none";

        if (btnPreviewPdf) {
            btnPreviewPdf.classList.add('btn-primary');
            btnPreviewPdf.classList.remove('btn-outline-primary');
        }
        if (btnPreviewWa) {
            btnPreviewWa.classList.add('btn-outline-primary');
            btnPreviewWa.classList.remove('btn-primary');
        }

        // init send_wa hidden from toggle (safety)
        if (toggleWa && sendWaHidden) {
            sendWaHidden.value = toggleWa.checked ? '1' : '0';
        }

        // when toggle changed -> update hidden and optionally switch preview
        if (toggleWa) {
            toggleWa.addEventListener('change', () => {
                if (sendWaHidden) sendWaHidden.value = toggleWa.checked ? '1' : '0';
                // UX: auto-show WA preview when toggle WA on
                if (toggleWa.checked && btnPreviewWa) {
                    btnPreviewWa.click();
                } else if (!toggleWa.checked && btnPreviewPdf) {
                    btnPreviewPdf.click();
                }
            });
        }

        /* ============================================================
           PREVIEW BUTTON SWITCHER
        ============================================================ */
        if (btnPreviewPdf && btnPreviewWa) {

            // === PDF PREVIEW ===
            btnPreviewPdf.addEventListener("click", () => {
                if (emailPreview) emailPreview.style.display = "block";
                if (waPreview) waPreview.style.display = "none";

                btnPreviewPdf.classList.add("btn-primary");
                btnPreviewPdf.classList.remove("btn-outline-primary");

                btnPreviewWa.classList.add("btn-outline-primary");
                btnPreviewWa.classList.remove("btn-primary");
            });

            // === WHATSAPP PREVIEW ===
            btnPreviewWa.addEventListener("click", () => {
                if (emailPreview) emailPreview.style.display = "none";
                if (waPreview) waPreview.style.display = "block";

                btnPreviewWa.classList.add("btn-primary");
                btnPreviewWa.classList.remove("btn-outline-primary");

                btnPreviewPdf.classList.add("btn-outline-primary");
                btnPreviewPdf.classList.remove("btn-primary");
            });
        }

        /* ============================================================
           PROGRESS UI ELEMENTS
        ============================================================ */
        const sendingStatusWrap = document.getElementById('sendingStatusWrap');
        const progressInner = document.getElementById('progressInner');
        const progressLabel = document.getElementById('progressLabel');
        const countSuccessEl = document.getElementById('countSuccess');
        const countFailedEl = document.getElementById('countFailed');
        const sendingLog = document.getElementById('sendingLog');


        /* ============================================================
           HELPERS
        ============================================================ */

        function getRowCheckboxes() {
            return Array.from(document.querySelectorAll('.payroll-row-checkbox'));
        }

        function extractRowFieldsFromCheckbox(cb) {
            const tr = cb.closest('tr');
            const dataName = cb.dataset.name || cb.getAttribute('data-name') || '';

            const dataJabatan = cb.dataset.jabatan || cb.getAttribute('data-jabatan') || null;
            const dataHari = cb.dataset.hari || cb.getAttribute('data-hari') || null;

            if (tr) {
                return {
                    name: tr.querySelector('td:nth-child(2)')?.innerText.trim() || dataName,
                    jabatan: tr.querySelector('td:nth-child(3)')?.innerText.trim() || dataJabatan,
                    jumlahMasuk: tr.querySelector('td:nth-child(4)')?.innerText.trim() || dataHari,
                    tr
                };
            }
            return {
                name: dataName,
                jabatan: dataJabatan,
                jumlahMasuk: dataHari,
                tr: null
            };
        }


        /* ============================================================
           BUILD PREVIEW LIST + CALL PDF + WA PREVIEW
        ============================================================ */
        function refreshSelectionUI() {
            try {
                const cbs = getRowCheckboxes().filter(cb => cb.checked);
                const ids = cbs.map(cb => cb.value);
                selectedIdsInput.value = JSON.stringify(ids);

                previewList.innerHTML = '';

                cbs.forEach(cb => {
                    const fields = extractRowFieldsFromCheckbox(cb);
                    const name = fields.name && fields.name !== 'null' ?
                        fields.name :
                        ('User ' + cb.value);

                    const jabatan = fields.jabatan;

                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className =
                        "list-group-item list-group-item-action d-flex justify-content-between align-items-start";
                    item.dataset.id = cb.value;

                    item.innerHTML = `
                    <div><strong>${name}</strong></div>
                    <div class="status-badge"><small class="text-muted">idle</small></div>
                `;

                    const statusBadge = item.querySelector(".status-badge");

                    item.addEventListener("click", function() {

                        /* ================== PREVIEW PDF ================== */
                        const url = "{{ url('/penggajian/gaji/preview-slip') }}/" + cb.value;
                        let frame = document.getElementById('slipFrame');
                        frame.src = url;

                        /* ================== PREVIEW WA ================== */
                        if (waSlipFrame) waSlipFrame.src = url;

                        // fetch info WA template
                        fetch("{{ url('/penggajian/gaji/get-info') }}/" + cb.value)
                            .then(res => res.json())
                            .then(info => {
                                const bulan = info.bulan || 'bulan ini';
                                const footer = info.wa_footer;
                                if (waTextPreview) {
                                    waTextPreview.textContent =
                                        `Halo rekan-rekan,

                                        Berikur terlampir slip gaji untuk bulan ${bulan}.
                                        Mohon untuk dicek terlebih dahulu, dan apabila terdapat ketidaksesuaian, silakan konfirmasi paling lambat hari ini.

                                        Terima kasih atas perhatian dan kerja samanya.

                                        _${footer}_`;
                                }
                            });

                        previewList.querySelectorAll(".list-group-item").forEach(e => e
                            .classList.remove("active"));
                        item.classList.add("active");
                    });

                    item.updateStatus = function(state, msg) {
                        if (!statusBadge) return;
                        statusBadge.innerHTML = {
                            idle: '<small class="text-muted">idle</small>',
                            sending: '<small class="text-muted">mengirim…</small>',
                            success: '<small class="text-muted">✓ terkirim</small>',
                            failed: '<small class="text-muted">✕ gagal</small>'
                        } [state];

                        if (msg) {
                            const p = document.createElement("div");
                            p.style.fontSize = "12px";
                            p.style.marginTop = "4px";
                            p.textContent = `#${item.dataset.id}: ${msg}`;
                            sendingLog.prepend(p);
                        }
                    };

                    previewList.appendChild(item);
                });

                const first = previewList.querySelector(".list-group-item");
                if (first) first.click();
                else if (previewContent) previewContent.innerHTML =
                    `<div class='text-muted'>Pilih pegawai untuk melihat preview.</div>`;

            } catch (err) {
                console.error("refreshSelectionUI error", err);
            }
        }


        /* ============================================================
           MODAL SHOW EVENT
        ============================================================ */
        modal.addEventListener("show.bs.modal", function() {
            refreshSelectionUI();
        });

        /* ============================================================
           CHECKBOX CHANGE EVENT
        ============================================================ */
        document.addEventListener("change", function(ev) {
            if (ev.target.classList.contains("payroll-row-checkbox")) {
                refreshSelectionUI();
            }
        });


        /* ============================================================
           AJAX SEND SEQUENTIAL (with send_wa support)
        ============================================================ */
        const singleSendEndpoint = "{{ route('penggajian.kirim-slip-single') }}";

        async function sendSequential(ids, sendEmailFlag = 1, sendWaFlag = 0) {
            sendingStatusWrap.style.display = 'block';
            progressInner.style.width = '0%';
            progressLabel.textContent = `Mengirim 0 / ${ids.length}`;
            countSuccessEl.textContent = '0';
            countFailedEl.textContent = '0';
            sendingLog.innerHTML = '';
            btnSendSlip.disabled = true;
            btnSendSlip.innerHTML = 'Mengirim…';

            let successCount = 0,
                failedCount = 0;
            const itemMap = {};
            previewList.querySelectorAll('.list-group-item').forEach(it => {
                itemMap[it.dataset.id] = it;
                if (it.updateStatus) it.updateStatus('idle');
            });

            for (let i = 0; i < ids.length; i++) {

                const id = ids[i];
                const item = itemMap[id];
                if (item?.updateStatus) item.updateStatus('sending', 'Mulai dikirim');

                const payload = new FormData();
                payload.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                payload.append('id', id);
                payload.append('send_email', sendEmailFlag ? '1' : '0');
                payload.append('send_wa', sendWaFlag ? '1' : '0'); // <-- kirim flag WA

                try {
                    const res = await fetch(singleSendEndpoint, {
                        method: 'POST',
                        body: payload
                    });

                    let ok = res.ok;
                    let responseJson = null;
                    try {
                        responseJson = await res.json();
                    } catch {}

                    if (responseJson?.skipped) {
                        successCount++;
                        item.updateStatus('success', 'Sudah ada — dikirim ulang');
                    } else if (ok) {
                        successCount++;
                        item.updateStatus('success', 'Terkirim');
                    } else {
                        failedCount++;
                        item.updateStatus('failed', 'Gagal (server)');
                    }

                } catch (e) {
                    failedCount++;
                    item.updateStatus('failed', 'Gagal network');
                }

                const pct = Math.round(((i + 1) / ids.length) * 100);
                progressInner.style.width = pct + '%';
                progressLabel.textContent = `Mengirim ${i + 1} / ${ids.length}`;
                countSuccessEl.textContent = successCount;
                countFailedEl.textContent = failedCount;

                await new Promise(r => setTimeout(r, 200));
            }

            btnSendSlip.disabled = false;
            btnSendSlip.innerHTML = 'Kirim Slip Terpilih';

            progressLabel.textContent = `Selesai — Berhasil ${successCount}, Gagal ${failedCount}`;

            return {
                success: successCount,
                failed: failedCount
            };
        }


        /* ============================================================
           FORM SUBMIT
        ============================================================ */
        formKirim.addEventListener("submit", async function(ev) {
            ev.preventDefault();

            const ids = JSON.parse(selectedIdsInput.value || '[]');
            if (!ids.length) return alert("Tidak ada pegawai terpilih.");
            if (ids.length >= 50 && !confirm(`Anda akan mengirim ${ids.length} slip. Lanjutkan?`))
                return;

            btnSendSlip.disabled = true;
            chkSendEmail.disabled = true;

            const sendEmail = chkSendEmail.checked ? 1 : 0;
            const sendWa = toggleWa && toggleWa.checked ? 1 : 0;

            const result = await sendSequential(ids, sendEmail, sendWa);

            btnSendSlip.disabled = false;
            chkSendEmail.disabled = false;

            alert(`Proses selesai. Berhasil: ${result.success}, Gagal: ${result.failed}`);
            if (result.failed === 0) window.location.reload();
        });

    });
</script>

<style>
    #previewList .list-group-item .status-badge {
        min-width: 70px;
        text-align: right;
    }

    #previewList .list-group-item.active {
        background-color: #0d6efd;
        color: #fff;
    }
</style>
