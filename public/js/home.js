document.addEventListener("DOMContentLoaded", function () {
    // ELEMENT DECLARATION
    const table = document.getElementById("userTable");
    const tbody = table ? table.querySelector("tbody") : null;

    const searchInput = document.getElementById("searchInput");
    const clearSearch = document.getElementById("clearSearch");
    const departmentFilter = document.getElementById("departmentFilter");
    const statusFilter = document.getElementById("statusFilter");

    // =============================
    // FILTER TABLE
    // =============================
    function filterTable() {
        if (!tbody) return;

        const keyword = searchInput ? searchInput.value.toLowerCase() : "";
        const selectedDept = departmentFilter
            ? departmentFilter.value.toLowerCase()
            : "";
        const selectedStatus = statusFilter ? statusFilter.value : "";

        const rows = tbody.querySelectorAll("tr");

        rows.forEach((row) => {
            const text = row.innerText.toLowerCase();

            const deptCell = row.cells[10]
                ? row.cells[10].innerText.trim().toLowerCase()
                : "";

            const statusCell = row.cells[13]
                ? row.cells[13].innerText.trim().toLowerCase()
                : "";

            const isActive = statusCell === "aktif";

            const matchSearch = text.includes(keyword);
            const matchDept = selectedDept === "" || deptCell === selectedDept;

            let matchStatus = true;

            if (selectedStatus === "1") {
                matchStatus = isActive;
            }

            if (selectedStatus === "0") {
                matchStatus = !isActive;
            }

            row.style.display =
                matchSearch && matchDept && matchStatus ? "" : "none";
        });

        if (clearSearch) {
            clearSearch.style.display = keyword ? "block" : "none";
        }
    }

    if (searchInput) {
        searchInput.addEventListener("input", filterTable);
    }

    if (departmentFilter) {
        departmentFilter.addEventListener("change", filterTable);
    }

    if (statusFilter) {
        statusFilter.addEventListener("change", filterTable);
    }

    if (clearSearch && searchInput) {
        clearSearch.addEventListener("click", function () {
            searchInput.value = "";
            filterTable();
        });
    }

    // Jalankan filter saat pertama load
    filterTable();

    // =============================
    // SORTING
    // =============================
    if (tbody) {
        let sortState = {
            direction: "asc",
        };

        function sortTable(keyExtractor) {
            const rows = Array.from(tbody.querySelectorAll("tr")).filter(
                (row) => row.cells.length > 0,
            );

            const currentDir = sortState.direction;

            rows.sort((a, b) => {
                const aText = keyExtractor(a);
                const bText = keyExtractor(b);

                return currentDir === "asc"
                    ? aText.localeCompare(bText)
                    : bText.localeCompare(aText);
            });

            tbody.innerHTML = "";
            rows.forEach((r) => tbody.appendChild(r));

            sortState.direction = currentDir === "asc" ? "desc" : "asc";
        }

        document.querySelectorAll(".sortable").forEach((header) => {
            header.addEventListener("click", () => {
                const column = header.dataset.column;

                document
                    .querySelectorAll(".sort-icon")
                    .forEach((icon) => (icon.textContent = "↕"));

                // =============================
                // SORT DEPARTMENT
                // =============================
                if (column === "cabang") {
                    sortTable((row) =>
                        row.cells[10]
                            ? row.cells[10].innerText.trim().toLowerCase()
                            : "",
                    );

                    document.getElementById("sortIconCabang").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }

                // =============================
                // SORT ROLE
                // =============================
                if (column === "role") {
                    const roleOrder = {
                        HRD: 1,
                        Supervisor: 2,
                        Captain: 3,
                        "Senior Crew": 4,
                        Crew: 5,
                    };

                    const rows = Array.from(
                        tbody.querySelectorAll("tr"),
                    ).filter((row) => row.cells.length > 0);

                    const currentDir = sortState.direction;

                    rows.sort((a, b) => {
                        const roleA = a.cells[12]
                            ? a.cells[12].innerText.trim()
                            : "";
                        const roleB = b.cells[12]
                            ? b.cells[12].innerText.trim()
                            : "";

                        const rankA = roleOrder[roleA] || 999;
                        const rankB = roleOrder[roleB] || 999;

                        return currentDir === "asc"
                            ? rankA - rankB
                            : rankB - rankA;
                    });

                    tbody.innerHTML = "";
                    rows.forEach((r) => tbody.appendChild(r));

                    sortState.direction = currentDir === "asc" ? "desc" : "asc";

                    document.getElementById("sortIconRole").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }

                // =============================
                // SORT NAMA
                // =============================
                if (column === "nama") {
                    sortTable((row) =>
                        row.cells[6]
                            ? row.cells[6].innerText.trim().toLowerCase()
                            : "",
                    );

                    document.getElementById("sortIconNama").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }

                // =============================
                // SORT NAMA LENGKAP
                // =============================
                if (column === "fullname") {
                    sortTable((row) =>
                        row.cells[7]
                            ? row.cells[7].innerText.trim().toLowerCase()
                            : "",
                    );

                    document.getElementById("sortIconFullname").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }

                // =============================
                // SORT TANGGAL MASUK
                // =============================
                if (column === "tanggal") {
                    const rows = Array.from(
                        tbody.querySelectorAll("tr"),
                    ).filter((row) => row.cells.length > 0);

                    const currentDir = sortState.direction;

                    rows.sort((a, b) => {
                        const dateA = new Date(a.cells[9]?.innerText.trim());
                        const dateB = new Date(b.cells[9]?.innerText.trim());

                        return currentDir === "asc"
                            ? dateA - dateB
                            : dateB - dateA;
                    });

                    tbody.innerHTML = "";
                    rows.forEach((r) => tbody.appendChild(r));

                    sortState.direction = currentDir === "asc" ? "desc" : "asc";

                    document.getElementById("sortIconTanggal").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }

                // =============================
                // SORT FINGER ID
                // =============================
                if (column === "finger") {
                    const rows = Array.from(
                        tbody.querySelectorAll("tr"),
                    ).filter((row) => row.cells.length > 0);

                    const currentDir = sortState.direction;

                    rows.sort((a, b) => {
                        const aVal =
                            parseInt(a.cells[8]?.innerText.trim()) || 0;
                        const bVal =
                            parseInt(b.cells[8]?.innerText.trim()) || 0;

                        return currentDir === "asc" ? aVal - bVal : bVal - aVal;
                    });

                    tbody.innerHTML = "";
                    rows.forEach((r) => tbody.appendChild(r));

                    sortState.direction = currentDir === "asc" ? "desc" : "asc";

                    document.getElementById("sortIconFinger").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }

                // =============================
                // SORT PAYROLL DEPARTMENT
                // =============================
                if (column === "payroll") {
                    sortTable((row) =>
                        row.cells[11]
                            ? row.cells[11].innerText.trim().toLowerCase()
                            : "",
                    );

                    document.getElementById("sortIconPayroll").textContent =
                        sortState.direction === "asc" ? "▲" : "▼";
                }
            });
        });
    }

    // =============================
    // TOAST
    // =============================
    window.showToast = function (message, variant = "primary") {
        const container = document.getElementById("toastContainer");
        if (!container) return;

        const toastEl = document.createElement("div");

        toastEl.className = `toast align-items-center text-bg-${variant} border-0 mb-2`;

        toastEl.setAttribute("role", "alert");
        toastEl.setAttribute("aria-live", "assertive");
        toastEl.setAttribute("aria-atomic", "true");

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-semibold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl, { delay: 6000 });

        toast.show();

        toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
    };
});
