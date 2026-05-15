// schedule2.js

document.addEventListener("DOMContentLoaded", function () {
    /*
    |--------------------------------------------------------------------------
    | ADD AGENDA (AJAX SUBMIT)
    |--------------------------------------------------------------------------
    */
    const agendaForm = document.getElementById("addAgendaForm");

    if (agendaForm) {
        agendaForm.addEventListener("submit", async function (e) {
            e.preventDefault();

            const fd = {
                title: document.getElementById("agenda_title")?.value,
                description:
                    document.getElementById("agenda_description")?.value,
                start_at: document.getElementById("agenda_start_at")?.value,
                end_at: document.getElementById("agenda_end_at")?.value || null,
                is_all_day: document.getElementById("agenda_all_day")?.checked
                    ? 1
                    : 0,
            };

            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            try {
                const res = await fetch(`${window.appBaseUrl}/magenda`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": token || "",
                    },
                    body: JSON.stringify(fd),
                });

                if (!res.ok) throw res;

                const modal = document.getElementById("addAgendaModal");
                bootstrap.Modal.getInstance(modal)?.hide();

                if (window.scheduleCalendar?.refetchEvents) {
                    window.scheduleCalendar.refetchEvents();
                } else {
                    location.reload();
                }
            } catch (err) {
                let msg = "Gagal menyimpan agenda";
                try {
                    const j = await err.json();
                    msg = j.message || msg;
                } catch (e) {}
                alert(msg);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FULLCALENDAR
    |--------------------------------------------------------------------------
    */
    const calendarEl = document.getElementById("contractCalendar");
    if (!calendarEl) return;

    window.scheduleCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        height: "auto",
        eventDisplay: "list-item",
        dayMaxEvents: true,

        events: async function (fetchInfo, successCallback) {
            try {
                const [contractsRes, agendasRes] = await Promise.all([
                    fetch(`${window.appBaseUrl}/schedule/contract/calendar`),
                    fetch(
                        `${window.appBaseUrl}/magenda?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`,
                    ),
                ]);

                const contracts = await contractsRes.json();
                const agendasRaw = await agendasRes.json();

                const agendas = agendasRaw.map((a) => ({
                    id: "magenda-" + (a.nid || a.id),
                    title: a.title,
                    start: a.start_at ? a.start_at.split("T")[0] : a.start_at,
                    end: a.end_at ? a.end_at.split("T")[0] : a.end_at,
                    allDay: a.is_all_day == 1 || a.is_all_day === true,
                    color: a.color || "#3B82F6",
                    display: "list-item",
                }));

                successCallback([...(contracts || []), ...agendas]);
            } catch (e) {
                successCallback([]);
            }
        },

        dateClick(info) {
            document.getElementById("dateInfoTitle").innerText =
                `Keterangan – ${info.dateStr}`;

            Promise.all([
                fetch(
                    `${window.appBaseUrl}/schedule/contract/by-date?date=${info.dateStr}`,
                ).then((r) => r.json()),
                fetch(
                    `${window.appBaseUrl}/magenda/by-date/${info.dateStr}`,
                ).then((r) => r.json()),
            ]).then(([contracts, agendas]) => {
                let body = "";

                if ((contracts?.length || 0) + (agendas?.length || 0) === 0) {
                    body = `<p class="text-success">Tidak ada kontrak berakhir atau agenda hari ini</p>`;
                } else {
                    body = `<ul class="list-group">`;

                    (contracts || []).forEach((c) => {
                        body += `
                            <li class="list-group-item d-flex justify-content-between">
                                <span>${c.name}</span>
                                <span class="badge bg-warning">${c.type}</span>
                            </li>`;
                    });

                    (agendas || []).forEach((a) => {
                        body += `
                            <li class="list-group-item d-flex justify-content-between">
                                <span>${a.title}</span>
                                <span class="badge bg-info text-dark">Agenda</span>
                            </li>`;
                    });

                    body += `</ul>`;
                }

                document.getElementById("dateInfoBody").innerHTML = body;

                // Add Contract
                const btnAddContract =
                    document.getElementById("btnAddContract");
                if (btnAddContract) {
                    btnAddContract.onclick = () => {
                        document.getElementById("contract_start").value =
                            info.dateStr;
                        bootstrap.Modal.getInstance(
                            document.getElementById("dateInfoModal"),
                        ).hide();

                        bootstrap.Modal.getOrCreateInstance(
                            document.getElementById("addContractModal"),
                        ).show();
                    };
                }

                // Add Agenda
                const btnAddAgenda = document.getElementById("btnAddAgenda");
                if (btnAddAgenda) {
                    btnAddAgenda.onclick = () => {
                        document.getElementById("agenda_start_at").value =
                            info.dateStr;
                        document.getElementById("agenda_end_at").value =
                            info.dateStr;

                        bootstrap.Modal.getInstance(
                            document.getElementById("dateInfoModal"),
                        ).hide();

                        bootstrap.Modal.getOrCreateInstance(
                            document.getElementById("addAgendaModal"),
                        ).show();
                    };
                }

                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById("dateInfoModal"),
                ).show();
            });
        },

        eventClick(info) {
            const modal = document.getElementById(
                "editContractModal" + info.event.id,
            );
            if (modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        },
    });

    window.scheduleCalendar.render();
});
