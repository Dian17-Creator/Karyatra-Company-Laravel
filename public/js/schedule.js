document.addEventListener("DOMContentLoaded", function () {
    /* ==========================================================
       🟢 1. Nama file saat import jadwal
    ========================================================== */
    const fileInput = document.querySelector(".custom-file-input");
    if (fileInput) {
        fileInput.addEventListener("change", function (e) {
            const fileName = e.target.value.split("\\").pop();
            const label = e.target.nextElementSibling;
            if (label) {
                label.classList.add("selected");
                label.innerHTML = fileName;
            }
        });
    }

    /* ==========================================================
       🟢 2. Import Jadwal via Fetch API
    ========================================================== */
    const importForm = document.getElementById("importScheduleForm");
    if (importForm) {
        importForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');

            if (btn) {
                btn.disabled = true;
                btn.innerHTML =
                    "<i class='fa fa-spinner fa-spin'></i> Mengimpor...";
            }

            fetch(this.action, {
                method: "POST",
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) throw new Error("Server error");
                    return response.text();
                })
                .then(() => {
                    const modalEl = document.getElementById(
                        "importScheduleModal",
                    );
                    const modal = modalEl
                        ? bootstrap.Modal.getInstance(modalEl)
                        : null;
                    if (modal) modal.hide();

                    alert("✅ Import berhasil diproses!");
                    location.reload();
                })
                .catch(() => {
                    alert("❌ Gagal mengimpor file, pastikan formatnya benar.");
                })
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = "Import Data";
                    }
                });
        });
    }

    /* ==========================================================
       🟢 3. Filter tanggal otomatis
    ========================================================== */
    const startDate = document.getElementById("start_date");
    const endDate = document.getElementById("end_date");
    const filter = document.getElementById("filterPeriode");

    if (filter && startDate && endDate) {
        filter.addEventListener("change", function () {
            const today = new Date();
            let start, end;

            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, "0");
                const d = String(date.getDate()).padStart(2, "0");
                return `${y}-${m}-${d}`;
            };

            switch (this.value) {
                case "this_week":
                    const day = today.getDay();
                    start = new Date(today);
                    start.setDate(today.getDate() - day + 1);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    break;

                case "last_week":
                    start = new Date(today);
                    start.setDate(today.getDate() - today.getDay() - 6);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    break;

                case "next_week":
                    start = new Date(today);
                    start.setDate(today.getDate() - today.getDay() + 8);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    break;

                case "this_month":
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    end = new Date(
                        today.getFullYear(),
                        today.getMonth() + 1,
                        0,
                    );
                    break;

                case "next_month":
                    start = new Date(
                        today.getFullYear(),
                        today.getMonth() + 1,
                        1,
                    );
                    end = new Date(
                        today.getFullYear(),
                        today.getMonth() + 2,
                        0,
                    );
                    break;

                default:
                    start = end = null;
            }

            if (start && end) {
                startDate.value = formatDate(start);
                endDate.value = formatDate(end);
            }
        });
    }

    /* ==========================================================
       🟢 4. Kalkulasi akhir kontrak (Add Modal)
    ========================================================== */
    const contractStart = document.getElementById("contract_start");
    const contractEnd = document.getElementById("contract_end");
    const contractDuration = document.getElementById("contract_duration");
    const addContractModal = document.getElementById("addContractModal");

    if (contractStart && contractEnd && contractDuration && addContractModal) {
        const formatDate = (date) => {
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, "0");
            const dd = String(date.getDate()).padStart(2, "0");
            return `${yyyy}-${mm}-${dd}`;
        };

        const calculateEndDate = () => {
            const start = new Date(contractStart.value);
            const months = parseInt(contractDuration.value);
            if (isNaN(start) || isNaN(months)) return;

            const end = new Date(start);
            end.setMonth(end.getMonth() + months);
            end.setDate(end.getDate() - 1);

            contractEnd.value = formatDate(end);
        };

        addContractModal.addEventListener("show.bs.modal", function () {
            contractStart.value = formatDate(new Date());
            contractEnd.value = "";
            if (contractDuration.value) calculateEndDate();
        });

        contractDuration.addEventListener("change", calculateEndDate);
        contractStart.addEventListener("change", calculateEndDate);
    }

    /* ==========================================================
       🟢 5. Kalkulasi akhir kontrak (Edit Modal)
    ========================================================== */
    document.querySelectorAll('[id^="editContractModal"]').forEach((modal) => {
        const startInput = modal.querySelector('input[name="dstart"]');
        const endInput = modal.querySelector('input[name="dend"]');
        const durationSelect = modal.querySelector('select[name="nterm"]');

        if (!startInput || !endInput || !durationSelect) return;

        const formatDate = (date) => {
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, "0");
            const dd = String(date.getDate()).padStart(2, "0");
            return `${yyyy}-${mm}-${dd}`;
        };

        const calculateEndDate = () => {
            const start = new Date(startInput.value);
            const months = parseInt(durationSelect.value);
            if (isNaN(start) || isNaN(months)) return;

            const end = new Date(start);
            end.setMonth(end.getMonth() + months);
            end.setDate(end.getDate() - 1);

            endInput.value = formatDate(end);
        };

        durationSelect.addEventListener("change", function () {
            if (!startInput.value) {
                alert("⚠️ Harap isi tanggal mulai terlebih dahulu.");
                durationSelect.value = "";
                return;
            }
            calculateEndDate();
        });

        startInput.addEventListener("change", function () {
            if (durationSelect.value) calculateEndDate();
        });
    });

    /* ==========================================================
       🟢 6. VALIDASI SHIFT (ADD & EDIT)
    ========================================================== */
    function validateShiftForm(form, e) {
        const dstart = form.querySelector('[name="dstart"]')?.value;
        const dend = form.querySelector('[name="dend"]')?.value;
        const dstart2 = form.querySelector('[name="dstart2"]')?.value;
        const dend2 = form.querySelector('[name="dend2"]')?.value;

        const toMinutes = (time) => {
            if (!time || !time.includes(":")) return null;
            const [h, m] = time.split(":").map(Number);
            return h * 60 + m;
        };

        const s1Start = toMinutes(dstart);
        const s1End = toMinutes(dend);

        // ================= SHIFT UTAMA =================
        if (s1Start !== null && s1End !== null && s1End <= s1Start) {
            e.preventDefault();
            alert("Jam selesai harus lebih besar dari jam mulai (shift utama)");
            return false;
        }

        // ================= SHIFT SPLIT =================
        if (dstart2 || dend2) {
            if (!dstart2 || !dend2) {
                e.preventDefault();
                alert("Jam split harus diisi lengkap (mulai & selesai)");
                return false;
            }

            const s2Start = toMinutes(dstart2);
            const s2End = toMinutes(dend2);

            if (s2End <= s2Start) {
                e.preventDefault();
                alert(
                    "Jam selesai split harus lebih besar dari jam mulai split",
                );
                return false;
            }

            // ✅ OVERLAP FIX (INI KUNCINYA)
            const overlap = s2Start < s1End && s2End > s1Start;

            if (overlap) {
                e.preventDefault();
                alert("Jam split tidak boleh overlap dengan shift utama");
                return false;
            }
        }

        return true;
    }

    // Add Shift
    const addShiftForm = document.querySelector("#addShiftModal form");
    if (addShiftForm) {
        addShiftForm.addEventListener("submit", function (e) {
            validateShiftForm(this, e);
        });
    }

    // Edit Shift
    document.querySelectorAll('[id^="editShiftModal"] form').forEach((form) => {
        form.addEventListener("submit", function (e) {
            validateShiftForm(this, e);
        });
    });
});
