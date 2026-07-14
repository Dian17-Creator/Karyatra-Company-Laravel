// GLOBAL STATE
let selectedDeptId = "";
let selectedDeptName = "Semua Departemen";

let attendanceData = [];
let currentPage = 1;
const rowsPerPage = 10;
let attendanceStatus = "present";

// DEPARTMENT FILTER
function selectDepartment(id, name) {
    selectedDeptId = id;
    selectedDeptName = name;

    const deptInput = document.getElementById("deptId");
    const deptLabel = document.getElementById("deptFilterLabel");

    if (deptInput) deptInput.value = id;
    if (deptLabel) deptLabel.innerText = name;
}

function applyDeptFilter() {
    loadAttendance();
}

function resetDeptFilter() {
    selectedDeptId = "";
    selectedDeptName = "Semua Departemen";

    const deptInput = document.getElementById("deptId");
    const deptLabel = document.getElementById("deptFilterLabel");

    if (deptInput) deptInput.value = "";
    if (deptLabel) deptLabel.innerText = "Semua Departemen";

    loadAttendance();
}

// DATE FILTER
function updateFilterLabel(start, end) {
    const opts = { day: "2-digit", month: "short", year: "numeric" };
    const s = new Date(start);
    const e = new Date(end);

    const label = document.getElementById("filterLabel");
    if (!label) return;

    label.innerText =
        start === end
            ? s.toLocaleDateString("id-ID", opts)
            : `${s.toLocaleDateString("id-ID", opts)} – ${e.toLocaleDateString("id-ID", opts)}`;
}

function formatDateLocal(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function setRange(type) {
    let start = null;
    let end = null;

    switch (type) {
        case "today": {
            const d = new Date();
            start = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            end = new Date(start);
            break;
        }

        case "yesterday": {
            const d = new Date();
            d.setDate(d.getDate() - 1);
            start = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            end = new Date(start);
            break;
        }

        case "7days": {
            end = new Date();
            start = new Date();
            start.setDate(end.getDate() - 6);
            break;
        }

        case "30days": {
            end = new Date();
            start = new Date();
            start.setDate(end.getDate() - 29);
            break;
        }

        case "thisMonth": {
            const d = new Date();
            start = new Date(d.getFullYear(), d.getMonth(), 1); // ✅ 01
            end = new Date(d.getFullYear(), d.getMonth(), d.getDate()); // ✅ hari ini
            break;
        }

        case "lastMonth": {
            const d = new Date();
            start = new Date(d.getFullYear(), d.getMonth() - 1, 1); // ✅ 01
            end = new Date(d.getFullYear(), d.getMonth(), 0); // ✅ last day prev month
            break;
        }

        default:
            return;
    }

    document.getElementById("startDate").value = formatDateLocal(start);
    document.getElementById("endDate").value = formatDateLocal(end);
}

function applyFilter() {
    const start = document.getElementById("startDate").value;
    const end = document.getElementById("endDate").value;

    updateFilterLabel(start, end);
    loadAttendance();
}

// LOAD ATTENDANCE DATA
function loadAttendance() {
    const loading = document.getElementById("loading");
    const tableBody = document.getElementById("attendanceTableBody");
    const errorAlert = document.getElementById("errorAlert");
    const pagination = document.getElementById("paginationControls");

    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const deptId = document.getElementById("deptId")?.value;
    const status = attendanceStatus; // 'present' or 'missing'

    currentPage = 1;

    loading?.classList.remove("d-none");
    errorAlert?.classList.add("d-none");
    pagination?.classList.add("d-none");
    tableBody.innerHTML = `<tr><td colspan=14" class="text-center">Loading...</td></tr>`;

    const params = new URLSearchParams();
    if (startDate) params.append("start", startDate);
    if (endDate) params.append("end", endDate);
    if (deptId) params.append("dept", deptId);
    // For attendance report (present) we use attendanceUrl; for missing use attendanceMissingUrl

    let url;
    if (status === "missing") {
        url = window.APP.attendanceMissingUrl;
    } else {
        url = window.APP.attendanceUrl;
    }

    if (params.toString()) url += "?" + params.toString();

    fetch(url)
        .then((res) => res.json())
        .then((res) => {
            loading?.classList.add("d-none");

            if (!res.success) throw new Error(res.message);

            // Normalize data shape so displayAttendancePage can render both report and missing list
            if (status === "missing") {
                // missing returns user_id, cname, dept_id, date
                attendanceData = (res.data || []).map((r) => ({
                    cname: r.cname || "",
                    date: r.date || r.dwork || "",
                    cschedname: "",
                    dstart: "",
                    in_time: "",
                    dend: "",
                    out_time: "",
                }));
            } else {
                attendanceData = res.data || [];
            }
            if (attendanceData.length) pagination?.classList.remove("d-none");

            displayAttendancePage();
        })
        .catch((err) => {
            loading?.classList.add("d-none");
            errorAlert.innerText = err.message;
            errorAlert?.classList.remove("d-none");
            tableBody.innerHTML = `<tr><td colspan="14" class="text-center text-danger">Gagal memuat data</td></tr>`;
        });
}

function formatTimeWithSource(time, source) {
    if (!time || time === "-") return "-";
    
    let badgeClass = "";
    let letter = "";
    let title = "";
    
    switch(source) {
        case 'face':
            badgeClass = 'bg-info text-white';
            letter = 'F';
            title = 'Face';
            break;
        case 'manual':
            badgeClass = 'bg-warning text-dark';
            letter = 'M';
            title = 'Manual';
            break;
        case 'forgot':
            badgeClass = 'bg-danger text-white';
            letter = 'L';
            title = 'Lupa Absen';
            break;
        case 'scan':
            badgeClass = 'bg-primary text-white';
            letter = 'S';
            title = 'Scan';
            break;
        default:
            return time;
    }
    
    return `
        <div class="d-flex align-items-center justify-content-center gap-1">
            <span class="fw-semibold">${time}</span>
            <span class="badge rounded-circle ${badgeClass}" 
                  style="width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 8px; padding: 0; font-weight: 700;" 
                  title="${title}">${letter}</span>
        </div>
    `;
}

// PAGINATION & TABLE RENDER
function displayAttendancePage() {
    const tableBody = document.getElementById("attendanceTableBody");
    const pageInfo = document.getElementById("pageInfo");
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");

    if (!attendanceData.length) {
        tableBody.innerHTML = `<tr><td colspan="14" class="text-center text-muted">Tidak ada data absensi</td></tr>`;
        return;
    }

    const start = (currentPage - 1) * rowsPerPage;
    const pageData = attendanceData.slice(start, start + rowsPerPage);

    tableBody.innerHTML = pageData
        .map((r) => {
            const rowClass = r.type === "izin" ? "table-warning" : "";

            const late =
                r.dstart && r.in_time
                    ? Math.max(
                          0,
                          Math.floor((toSec(r.in_time) - toSec(r.dstart)) / 60),
                      )
                    : 0;

            const overtime =
                r.dend && r.out_time
                    ? Math.max(
                          0,
                          Math.floor((toSec(r.out_time) - toSec(r.dend)) / 60),
                      )
                    : 0;

            const alasan = r.type === "izin" ? r.alasan || "-" : "-";

            return `
            <tr class="attendance-row ${rowClass}"
                data-user="${r.user_id || ""}"
                data-date="${r.date || ""}"
                data-type="${r.type || "attendance"}"
                style="cursor:pointer">

                <td>${r.cname || "-"}</td>
                <td>${r.date || "-"}</td>
                <td>${r.cschedname || "-"}</td>

                <td>${r.dstart || "-"}</td>
                <td>${formatTimeWithSource(r.in_time, r.in_source)}</td>
                <td>${r.dend || "-"}</td>
                <td>${formatTimeWithSource(r.out_time, r.out_source)}</td>

                <td>${r.dstart2 || "-"}</td>
                <td>${formatTimeWithSource(r.in_time2, r.in_source2)}</td>
                <td>${r.dend2 || "-"}</td>
                <td>${formatTimeWithSource(r.out_time2, r.out_source2)}</td>

                <td class="${late ? "text-late" : ""}">
                ${late ? late + " menit" : "-"}
                </td>

                <td class="${overtime ? "text-success" : ""}">
                ${overtime ? overtime + " menit" : "-"}
                </td>

                <td>${alasan || "-"}</td>
            </tr>
            `;
        })
        .join("");

    const totalPages = Math.ceil(attendanceData.length / rowsPerPage);
    pageInfo.innerText = `Halaman ${currentPage} dari ${totalPages}`;

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages;

    prevBtn.onclick = () =>
        currentPage > 1 && (currentPage--, displayAttendancePage());
    nextBtn.onclick = () =>
        currentPage < totalPages && (currentPage++, displayAttendancePage());
}

function toSec(t) {
    if (!t) return 0;
    const [h, m, s] = t.split(":").map(Number);
    return h * 3600 + m * 60 + s;
}

// EXPORT
document.getElementById("exportBtn")?.addEventListener("click", (e) => {
    e.preventDefault();

    const start = document.getElementById("startDate").value;
    const end = document.getElementById("endDate").value;

    const params = new URLSearchParams();
    if (start) params.append("start_date", start);
    if (end) params.append("end_date", end);

    let url = window.APP.exportUrl;
    if (params.toString()) url += "?" + params.toString();

    window.location.href = url;
});

// INIT
document.addEventListener("DOMContentLoaded", () => {
    const today = new Date().toISOString().split("T")[0];
    document.getElementById("startDate").value = today;
    document.getElementById("endDate").value = today;
    // wire up attendance status apply button and initialize label
    const applyBtn = document.getElementById("applyAttStatusBtn");
    applyBtn &&
        applyBtn.addEventListener("click", function () {
            var selected = document.querySelector(
                'input[name="att_status"]:checked',
            );
            var label = document.getElementById("attStatusLabel");
            if (selected && label) {
                attendanceStatus =
                    selected.value === "missing" ? "missing" : "present";
                label.textContent =
                    attendanceStatus === "missing"
                        ? "Belum Absen"
                        : "Sudah Absen";
                // reload data after applying
                loadAttendance();
            }
        });

    // ensure initial label reflects default
    (function initStatusLabel() {
        var initial = document.querySelector(
            'input[name="att_status"]:checked',
        );
        var label = document.getElementById("attStatusLabel");
        if (initial && label) {
            attendanceStatus =
                initial.value === "missing" ? "missing" : "present";
            label.textContent =
                attendanceStatus === "missing" ? "Belum Absen" : "Sudah Absen";
        }
    })();
    loadAttendance();
});

function syncDate() {
    const start = document.getElementById("startDate").value;

    if (start) {
        document.getElementById("endDate").value = start;
    }
}

document.addEventListener("click", function (e) {
    const row = e.target.closest(".attendance-row");
    if (!row) return;

    const user = row.dataset.user;
    const date = row.dataset.date;
    const type = row.dataset.type;

    const base = window.location.origin;

    let url;

    if (type === "izin") {
        // arahkan ke halaman izin
        url = `${base}/backoffice/requests/${user}`;
    } else {
        // arahkan ke log absensi
        url = `${base}/backoffice/logs/${user}?date=${date}`;
    }

    window.location.href = url;
});
