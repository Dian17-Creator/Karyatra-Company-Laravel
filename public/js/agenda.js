
    /* ========================================================================
       GLOBAL VARIABLES
    ======================================================================== */

    let selectedCalendarDate = null;

    /* ========================================================================
       UTILITY FUNCTIONS
    ======================================================================== */

    /**
     * Pad angka menjadi 2 digit (contoh: 1 → "01")
     */
    function pad(n) {
        return n.toString().padStart(2, '0');
    }

    /**
     * Parse string "yyyy-mm-dd" menjadi Date object
     */
    function parseYMD(ymd) {
        const parts = (ymd || '').split('-');
        if (parts.length < 3) return null;
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    }

    /**
     * Decode HTML entities sederhana (&lt; → <, &gt; → >)
     */
    function decodeHtml(s) {
        return s.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    }

    /**
     * Konversi string tanggal ke format datetime-local (yyyy-mm-ddThh:mm)
     * Mendukung format: yyyy-mm-dd, dd/mm/yyyy, dan yang sudah mengandung T
     */
    function asDatetimeLocal(dateStr) {
        if (!dateStr) return '';
        // sudah mengandung waktu
        if (dateStr.indexOf('T') !== -1) return dateStr;
        // format yyyy-mm-dd
        var m = dateStr.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        var now = new Date();
        if (m) {
            return m[1] + '-' + pad(m[2]) + '-' + pad(m[3]) +
                'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        }
        // format dd/mm/yyyy
        m = dateStr.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (m) {
            return m[3] + '-' + pad(m[2]) + '-' + pad(m[1]) +
                'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        }
        return dateStr;
    }

    /**
     * Konversi datetime string ke format datetime-local pendek (yyyy-mm-ddThh:mm)
     */
    function toDatetimeLocal(v) {
        if (!v) return '';
        if (v.indexOf('T') !== -1) return v.substring(0, 16);
        return v;
    }

    /**
     * Isi field date & time dari string datetime ISO
     */
    function fillDateTime(start, end) {
        if (start) {
            const s = start.split('T');
            document.getElementById('detail_start_date').value = s[0] || '';
            document.getElementById('detail_start_time').value = (s[1] || '').substring(0, 5);
        }
        if (end) {
            const e = end.split('T');
            document.getElementById('detail_end_date').value = e[0] || '';
            document.getElementById('detail_end_time').value = (e[1] || '').substring(0, 5);
        }
    }

    /**
     * Ambil CSRF token dari meta tag
     */
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Validasi reminder agar tidak kurang dari waktu sekarang
     * return:
     *  - true  => valid
     *  - false => ada reminder invalid
     */
    function validateReminderTime(startAt, reminders) {

    const now = new Date();
    const agendaDate = new Date(startAt);

    for (const r of reminders) {

        let ms = 0;
        const value = Number(r.value);

        // label indonesia
        let unitLabel = '';

        switch (r.unit) {

            case 'minute':
                ms = value * 60 * 1000;
                unitLabel = 'menit';
                break;

            case 'hour':
                ms = value * 60 * 60 * 1000;
                unitLabel = 'jam';
                break;

            case 'day':
                ms = value * 24 * 60 * 60 * 1000;
                unitLabel = 'hari';
                break;

            case 'week':
                ms = value * 7 * 24 * 60 * 60 * 1000;
                unitLabel = 'minggu';
                break;
        }

            // waktu reminder
            const reminderTime = new Date(agendaDate.getTime() - ms);

            // kalau reminder sudah lewat
            if (reminderTime <= now) {

                alert(
                    `Reminder ${value} ${unitLabel} sebelum agenda sudah lewat dari waktu sekarang`
                );

                return false;
            }
        }

        return true;
    }

    /* ========================================================================
       REMINDER ROW — MODAL TAMBAH AGENDA
    ======================================================================== */

    /**
     * Tambah baris reminder pada modal Tambah Agenda
     * @param {string} value - Nilai durasi reminder
     * @param {string} unit  - Satuan (minute|hour|day|week)
     */
    function addReminderRow(value = '', unit = 'day') {

        const list = document.getElementById('reminder_list');
        const row = document.createElement('div');

        row.className = 'row g-2 align-items-center mb-2';

        row.innerHTML = `
            <div class="col-5">
                <input
                    type="number"
                    min="1"
                    class="form-control reminder_value"
                    placeholder="Durasi"
                    value="${value}">
            </div>
            <div class="col-5">
                <select class="form-select reminder_unit">
                    <option value="minute" ${unit === 'minute' ? 'selected' : ''}>Menit Sebelum</option>
                    <option value="hour"   ${unit === 'hour'   ? 'selected' : ''}>Jam Sebelum</option>
                    <option value="day"    ${unit === 'day'    ? 'selected' : ''}>Hari Sebelum</option>
                    <option value="week"   ${unit === 'week'   ? 'selected' : ''}>Minggu Sebelum</option>
                </select>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger w-100 btn-remove-reminder">×</button>
            </div>
        `;

        list.appendChild(row);

        // Hapus baris reminder, nonaktifkan jika sudah kosong
        row.querySelector('.btn-remove-reminder')
            .addEventListener('click', function() {
                row.remove();
                if (list.children.length === 0) {
                    document.getElementById('reminder_enabled').value = '0';
                    document.getElementById('reminder_container').style.display = 'none';
                }
            });
    }

    /* ========================================================================
       REMINDER ROW — MODAL DETAIL AGENDA
    ======================================================================== */

    /**
     * Tambah baris reminder pada modal Detail Agenda
     * @param {string} value - Nilai durasi reminder
     * @param {string} unit  - Satuan (minute|hour|day|week)
     */
    function addDetailReminderRow(value = '', unit = 'day') {

        const list = document.getElementById('detail_reminder_list');
        const row = document.createElement('div');

        row.className = 'row g-2 align-items-center mb-2';

        row.innerHTML = `
            <div class="col-5">
                <input
                    type="number"
                    min="1"
                    class="form-control detail_reminder_value"
                    placeholder="Durasi"
                    value="${value}">
            </div>
            <div class="col-5">
                <select class="form-select detail_reminder_unit">
                    <option value="minute" ${unit === 'minute' ? 'selected' : ''}>Menit Sebelum</option>
                    <option value="hour"   ${unit === 'hour'   ? 'selected' : ''}>Jam Sebelum</option>
                    <option value="day"    ${unit === 'day'    ? 'selected' : ''}>Hari Sebelum</option>
                    <option value="week"   ${unit === 'week'   ? 'selected' : ''}>Minggu Sebelum</option>
                </select>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger w-100 btn-remove-detail-reminder">×</button>
            </div>
        `;

        list.appendChild(row);

        // Hapus baris reminder, nonaktifkan jika sudah kosong
        row.querySelector('.btn-remove-detail-reminder')
            .addEventListener('click', function() {
                row.remove();
                if (list.children.length === 0) {
                    document.getElementById('detail_reminder_enabled').value = '0';
                    document.getElementById('detail_reminder_container').style.display = 'none';
                }
            });
    }

    /* ========================================================================
       MAIN — DOMContentLoaded
    ======================================================================== */

    document.addEventListener('DOMContentLoaded', function() {

        /* ==================================================================
           1. CALENDAR INIT (FullCalendar)
        ================================================================== */

        const el = document.getElementById('contractCalendar');
        if (!el) return;

        let groupedContracts = {};

        const calendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            height: 550,
            fixedWeekCount: false,
            showNonCurrentDates: true,

            /* --------------------------------------------------------------
               1a. EVENTS FETCH + GROUP
               Mengambil data kontrak dan agenda, lalu mengelompokkan per tanggal
            -------------------------------------------------------------- */
            events: async function(fetchInfo, successCallback) {
                try {
                    const [ctrRes, agRes] = await Promise.all([
                        fetch(`${window.appBaseUrl}/schedule/contract/calendar`),
                        fetch(`${window.appBaseUrl}/magenda?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                    ]);

                    const ctr = await ctrRes.json();
                    const ag = await agRes.json();

                    groupedContracts = {};

                    // Group kontrak per tanggal
                    (ctr || []).forEach(e => {
                        const d = e.start;
                        if (!groupedContracts[d]) groupedContracts[d] = [];
                        groupedContracts[d].push(e.title);
                    });

                    // Group agenda per tanggal (termasuk multi-day)
                    (ag || []).forEach(a => {
                        const startDateStr = (a.start_at || '').split('T')[0] || a.start_at || null;
                        const endDateStr = (a.end_at || '').split('T')[0] || a.end_at || startDateStr;

                        if (!startDateStr) return;

                        const sDate = parseYMD(startDateStr);
                        const eDate = parseYMD(endDateStr) || sDate;
                        if (!sDate) return;

                        for (let d = new Date(sDate); d <= eDate; d.setDate(d.getDate() + 1)) {
                            const key = d.getFullYear() + '-' +
                                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                                String(d.getDate()).padStart(2, '0');
                            if (!groupedContracts[key]) groupedContracts[key] = [];
                            groupedContracts[key].push(a.title);
                        }
                    });

                    // Format untuk FullCalendar
                    const formatted = Object.keys(groupedContracts).map(date => ({
                        start: date,
                        allDay: true,
                        display: 'block',
                        extendedProps: { names: groupedContracts[date] }
                    }));

                    successCallback(formatted);

                } catch (e) {
                    successCallback([]);
                }
            },

            /* --------------------------------------------------------------
               1b. TOOLTIP — Tampilkan tooltip Bootstrap pada cell yang ada event
            -------------------------------------------------------------- */
            eventDidMount(info) {
                const names = info.event.extendedProps.names;
                if (!names) return;

                const cell = info.el.closest('.fc-daygrid-day');
                if (!cell) return;

                cell.classList.add('has-contract');

                // Hapus tooltip lama jika ada
                const old = bootstrap.Tooltip.getInstance(cell);
                if (old) old.dispose();

                cell.setAttribute('data-bs-toggle', 'tooltip');
                cell.setAttribute('data-bs-html', 'true');
                cell.setAttribute('data-bs-title', names.join('<br>'));

                new bootstrap.Tooltip(cell, {
                    container: 'body',
                    trigger: 'hover'
                });
            },

            /* --------------------------------------------------------------
               1c. DOT RENDER — Tampilkan jumlah event sebagai dot di cell
            -------------------------------------------------------------- */
            eventContent(info) {
                const names = info.event.extendedProps.names;
                const count = names.length;

                const dot = document.createElement('div');
                dot.className = 'fc-contract-dot';
                dot.innerText = count;

                return { domNodes: [dot] };
            },

            /* --------------------------------------------------------------
               1d. DATE CLICK — Buka modal info tanggal
               Menampilkan daftar kontrak & agenda pada tanggal yang diklik
            -------------------------------------------------------------- */
            dateClick(info) {
                selectedCalendarDate = info.dateStr;

                Promise.all([
                    fetch(`${window.appBaseUrl}/schedule/contract/by-date?date=${info.dateStr}`)
                        .then(r => r.json()),
                    fetch(`${window.appBaseUrl}/magenda/by-date/${info.dateStr}`)
                        .then(r => r.json())
                ]).then(([contracts, agendas]) => {

                    // Set judul modal
                    document.getElementById('dateInfoTitle').innerText =
                        `Keterangan – ${info.dateStr}`;

                    // Bangun konten body modal
                    let body = '';

                    if ((contracts?.length || 0) + (agendas?.length || 0) === 0) {
                        body = `<p class="text-success mb-0">Tidak ada kontrak berakhir atau agenda hari ini</p>`;
                    } else {
                        body = `<ul class="list-group">`;

                        // List kontrak
                        (contracts || []).forEach(c => {
                            body += `
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>${c.name}</span>
                                    <span class="badge bg-warning">${c.type}</span>
                                </li>`;
                        });

                        // List agenda (dengan data attributes untuk detail modal)
                        (agendas || []).forEach(a => {
                            const safeTitle = (a.title || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            const safeDesc = (a.description || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            const start = a.start_at || '';
                            const end = a.end_at || '';
                            const reminders = JSON.stringify(a.reminders || []);

                            body += `
                                <li class="list-group-item d-flex justify-content-between agenda-item"
                                    data-id="${a.id}"
                                    data-title="${safeTitle}"
                                    data-desc="${safeDesc}"
                                    data-start="${start}"
                                    data-end="${end}"
                                    data-reminders='${reminders}'>
                                    <span>${safeTitle}</span>
                                    <span class="badge bg-info text-dark">Agenda</span>
                                </li>`;
                        });

                        body += `</ul>`;
                    }

                    document.getElementById('dateInfoBody').innerHTML = body;

                    // Tombol tambah kontrak
                    document.getElementById('btnAddContract').onclick = () => {
                        document.getElementById('contract_start').value = info.dateStr;
                        bootstrap.Modal.getInstance(
                            document.getElementById('dateInfoModal')
                        ).hide();
                        bootstrap.Modal.getOrCreateInstance(
                            document.getElementById('addContractModal')
                        ).show();
                    };

                    // Tombol tambah agenda: isi tanggal default dan buka modal
                    const btnAddAgenda = document.getElementById('btnAddAgenda');
                    if (btnAddAgenda) {
                        btnAddAgenda.onclick = () => {
                            const startInput = document.getElementById('agenda_start_at');
                            const endInput = document.getElementById('agenda_end_at');

                            if (startInput) startInput.value = asDatetimeLocal(info.dateStr || '');

                            if (endInput) {
                                const s = asDatetimeLocal(info.dateStr || '');
                                if (s && s.indexOf('T') !== -1) {
                                    const d = new Date(s);
                                    const d2 = new Date(d.getTime() + 60 * 60 * 1000);
                                    if (startInput) startInput.value = s;
                                    endInput.value = d2.getFullYear() + '-' +
                                        pad(d2.getMonth() + 1) + '-' + pad(d2.getDate()) +
                                        'T' + pad(d2.getHours()) + ':' + pad(d2.getMinutes());
                                } else {
                                    endInput.value = asDatetimeLocal(info.dateStr || '');
                                }
                            }

                            bootstrap.Modal.getInstance(
                                document.getElementById('dateInfoModal')
                            ).hide();
                            bootstrap.Modal.getOrCreateInstance(
                                document.getElementById('addAgendaModal')
                            ).show();
                        };
                    }

                    // Tampilkan modal info tanggal
                    bootstrap.Modal.getOrCreateInstance(
                        document.getElementById('dateInfoModal')
                    ).show();

                    // Pasang click handler pada agenda items untuk buka detail modal
                    setTimeout(() => {
                        document.querySelectorAll('.agenda-item').forEach(el => {
                            el.style.cursor = 'pointer';
                            el.addEventListener('click', function() {
                                openDetailAgendaModal(this);
                            });
                        });
                    }, 10);
                });
            },

            eventClick() {
                return false;
            }
        });

        calendar.render();

        /* ==================================================================
           2. ADD AGENDA FORM — Submit handler (AJAX POST)
        ================================================================== */

        const addAgendaForm = document.getElementById('addAgendaForm');

        if (addAgendaForm) {
            addAgendaForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Bangun datetime dari field terpisah
                const startDate = document.getElementById('agenda_start_date').value;
                const startTime = document.getElementById('agenda_start_time').value;
                const endDate = document.getElementById('agenda_end_date').value;
                const endTime = document.getElementById('agenda_end_time').value;

                const start_at = startDate + 'T' + startTime;
                const end_at = endDate + 'T' + endTime;

                // Kumpulkan data reminder
                let reminders = [];
                document.querySelectorAll('#reminder_list .row').forEach(row => {
                    const value = row.querySelector('.reminder_value')?.value;
                    const unit = row.querySelector('.reminder_unit')?.value;
                    if (value && unit) {
                        reminders.push({ value, unit });
                    }
                });

                if (!validateReminderTime(start_at, reminders)) {
                    return;
                }

                // Payload
                const fd = {
                    title: document.getElementById('agenda_title').value,
                    description: document.getElementById('agenda_description').value,
                    start_at,
                    end_at,
                    is_all_day: document.getElementById('agenda_all_day')?.checked ? 1 : 0,
                    reminders
                };

                try {
                    const res = await fetch(`${window.appBaseUrl}/magenda`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify(fd)
                    });

                    const data = await res.json();

                    if (!res.ok) throw data;

                    bootstrap.Modal.getInstance(
                        document.getElementById('addAgendaModal')
                    )?.hide();

                    location.reload();

                } catch (err) {
                    alert(err.message || err.error || 'Gagal menyimpan agenda');
                }
            });
        }

        /* ==================================================================
           3. ADD AGENDA MODAL — Set default tanggal & waktu saat modal dibuka
        ================================================================== */

        const addAgendaModal = document.getElementById('addAgendaModal');

        if (addAgendaModal) {
            addAgendaModal.addEventListener('show.bs.modal', function() {
                const date = selectedCalendarDate || new Date().toISOString().slice(0, 10);

                document.getElementById('agenda_start_date').value = date;
                document.getElementById('agenda_end_date').value = date;
                document.getElementById('agenda_start_time').value = '08:00';
                document.getElementById('agenda_end_time').value = '09:00';
            });
        }

        /* ==================================================================
           4. DETAIL AGENDA — Update & Delete handler
        ================================================================== */

        const btnUpdate = document.getElementById('btnUpdateAgenda');
        const btnDelete = document.getElementById('btnDeleteAgenda');

        // Update agenda
        if (btnUpdate) {
            btnUpdate.addEventListener('click', async function() {
                const id = document.getElementById('detail_id').value;
                if (!id) return alert('ID agenda tidak ditemukan');

                // Kumpulkan data reminder jika aktif
                let reminders = [];
                if (document.getElementById('detail_reminder_enabled').value === '1') {
                    document.querySelectorAll('#detail_reminder_list .row').forEach(row => {
                        const value = row.querySelector('.detail_reminder_value')?.value;
                        const unit = row.querySelector('.detail_reminder_unit')?.value;
                        if (value && unit) {
                            reminders.push({ value, unit });
                        }
                    });
                }

                const start_at = document.getElementById('detail_start_date').value 
                + 
                'T' + document.getElementById('detail_start_time').value;


                if (!validateReminderTime(start_at, reminders)) {
                    return;
                }

                const fd = {
                    title: document.getElementById('detail_title').value,
                    description: document.getElementById('detail_desc').value,
                    start_at: document.getElementById('detail_start_date').value +
                        'T' + document.getElementById('detail_start_time').value,
                    end_at: document.getElementById('detail_end_date').value +
                        'T' + document.getElementById('detail_end_time').value,
                    is_all_day: 0,
                    reminders
                };

                try {
                    const res = await fetch(`${window.appBaseUrl}/magenda/${id}?_method=PUT`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify(fd)
                    });

                    if (!res.ok) throw res;

                    bootstrap.Modal.getInstance(
                        document.getElementById('modal_detail_agenda')
                    )?.hide();

                    location.reload();

                } catch (err) {
                    alert('Gagal menyimpan perubahan agenda');
                }
            });
        }

        // Hapus agenda
        if (btnDelete) {
            btnDelete.addEventListener('click', async function() {
                const id = document.getElementById('detail_id').value;

                if (!id) return alert('ID agenda tidak ditemukan');
                if (!confirm('Yakin ingin menghapus agenda ini?')) return;

                try {
                    const res = await fetch(`${window.appBaseUrl}/magenda/delete/${id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({})
                    });

                    await res.json();

                    bootstrap.Modal.getInstance(
                        document.getElementById('modal_detail_agenda')
                    )?.hide();

                    location.reload();

                } catch (err) {
                    alert('Gagal menghapus agenda');
                }
            });
        }

        /* ==================================================================
           5. REMINDER TOGGLE — Modal Tambah Agenda
           Tampil/sembunyikan container reminder berdasarkan dropdown
        ================================================================== */

        const reminderEnabled = document.getElementById('reminder_enabled');
        const reminderContainer = document.getElementById('reminder_container');
        const reminderList = document.getElementById('reminder_list');
        const btnAddReminder = document.getElementById('btnAddReminder');

        if (reminderEnabled) {
            reminderEnabled.addEventListener('change', function() {
                if (this.value === '1') {
                    reminderContainer.style.display = 'block';
                    // Otomatis buat 1 row pertama jika belum ada
                    if (reminderList.children.length === 0) {
                        addReminderRow();
                    }
                } else {
                    reminderContainer.style.display = 'none';
                    reminderList.innerHTML = '';
                }
            });
        }

        if (btnAddReminder) {
            btnAddReminder.addEventListener('click', function() {
                addReminderRow();
            });
        }

        /* ==================================================================
           6. REMINDER TOGGLE — Modal Detail Agenda
           Tampil/sembunyikan container reminder berdasarkan dropdown
        ================================================================== */

        const detailReminderEnabled = document.getElementById('detail_reminder_enabled');
        const detailReminderContainer = document.getElementById('detail_reminder_container');
        const detailReminderList = document.getElementById('detail_reminder_list');
        const btnAddDetailReminder = document.getElementById('btnAddDetailReminder');

        if (detailReminderEnabled) {
            detailReminderEnabled.addEventListener('change', function() {
                if (this.value === '1') {
                    detailReminderContainer.style.display = 'block';
                    // Otomatis buat 1 row pertama jika belum ada
                    if (detailReminderList.children.length === 0) {
                        addDetailReminderRow();
                    }
                } else {
                    detailReminderContainer.style.display = 'none';
                    detailReminderList.innerHTML = '';
                }
            });
        }

        if (btnAddDetailReminder) {
            btnAddDetailReminder.addEventListener('click', function() {
                addDetailReminderRow();
            });
        }

    });

    /* ========================================================================
       OPEN DETAIL AGENDA MODAL
       Mengisi field-field pada modal detail dari data attributes element
    ======================================================================== */

    function openDetailAgendaModal(el) {
        const id = el.getAttribute('data-id');
        const title = el.getAttribute('data-title') || '';
        const desc = el.getAttribute('data-desc') || '';
        const start = el.getAttribute('data-start') || '';
        const end = el.getAttribute('data-end') || '';
        const reminders = JSON.parse(el.getAttribute('data-reminders') || '[]');

        const modalEl = document.getElementById('modal_detail_agenda');
        if (!modalEl) return;

        // Isi field
        document.getElementById('detail_id').value = id || '';
        document.getElementById('detail_title').value = decodeHtml(title);
        document.getElementById('detail_desc').value = decodeHtml(desc);

        fillDateTime(start, end);

        // Load reminders
        const enabled = document.getElementById('detail_reminder_enabled');
        const container = document.getElementById('detail_reminder_container');
        const list = document.getElementById('detail_reminder_list');

        list.innerHTML = '';

        if (reminders.length > 0) {
            enabled.value = '1';
            container.style.display = 'block';
            reminders.forEach(r => {
                addDetailReminderRow(r.reminder_value, r.reminder_unit);
            });
        } else {
            enabled.value = '0';
            container.style.display = 'none';
        }

        // Tutup modal info tanggal dan buka modal detail
        bootstrap.Modal.getInstance(
            document.getElementById('dateInfoModal')
        )?.hide();

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
