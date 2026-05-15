// 1) EDIT PAYROLL (modal)
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-open-edit");
    if (!btn) return;

    const clean = (v) => (v == null ? "" : String(v).trim());
    const read = (name) => btn.getAttribute("data-" + name) ?? "";

    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val ?? "";
    };

    const form = document.getElementById("formEditPayroll");
    if (!form) return;

    // 🔥 RESET FORM (INI PENTING)
    form.reset();

    const id = read("id");
    const tpl = form.getAttribute("data-url");
    if (id && tpl) {
        form.action = tpl.replace(":id", id);
    }

    // ===== isi field =====
    set("mp_user_id", read("user-id"));
    set("mp_jumlah_masuk", clean(read("jumlah-masuk")));
    set("mp_gaji_harian", clean(read("gaji-harian")));
    set("mp_gaji_pokok", clean(read("gaji-pokok")));

    set("mp_tunjangan_makan", clean(read("tunjangan-makan")));
    set("mp_tunjangan_jabatan", clean(read("tunjangan-jabatan")));
    set("mp_tunjangan_transport", clean(read("tunjangan-transport")));
    set("mp_tunjangan_luar_kota", clean(read("tunjangan-luar-kota")));
    set("mp_tunjangan_masa_kerja", clean(read("tunjangan-masa-kerja")));
    set("mp_tunjangan_backup", clean(read("tunjangan-backup")));

    set("mp_gaji_lembur", clean(read("gaji-lembur")));
    set("mp_bonus_kehadiran", clean(read("bonus-kehadiran")));
    set("mp_tabungan_diambil", clean(read("tabungan-diambil")));

    set("mp_potongan_lain", clean(read("potongan-lain")));
    set("mp_potongan_tabungan", clean(read("potongan-tabungan")));
    set("mp_potongan_keterlambatan", clean(read("potongan-keterlambatan")));

    set("mp_note", read("note"));
    set("mp_reasonedit", read("reasonedit"));
});

// 2) SELECT ALL CHECKBOX
document.addEventListener("DOMContentLoaded", function () {
    const selectAll = document.getElementById("select_all");

    const getBoxes = () =>
        [...document.querySelectorAll(".payroll-row-checkbox")].filter(
            (cb) => cb.value && cb.value !== "" && cb.value !== "on",
        );

    const updateState = () => {
        const boxes = getBoxes();
        if (!boxes.length) return;

        const allChecked = boxes.every((b) => b.checked);
        const noneChecked = boxes.every((b) => !b.checked);

        selectAll.checked = allChecked;
        selectAll.indeterminate = !allChecked && !noneChecked;
    };

    if (selectAll) {
        selectAll.addEventListener("change", function () {
            getBoxes().forEach((cb) => (cb.checked = selectAll.checked));
        });
    }

    getBoxes().forEach((cb) => cb.addEventListener("change", updateState));
    updateState();
});

// 3) EXPORT EXCEL
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("btnExportSelected");
    const form = document.getElementById("formExportExcel");
    const hidden = document.getElementById("selected_ids");

    if (!btn || !form || !hidden) return;

    btn.addEventListener("click", function () {
        const boxes = [
            ...document.querySelectorAll(".payroll-row-checkbox"),
        ].filter((cb) => cb.value && cb.value !== "on");

        const selected = boxes.filter((cb) => cb.checked).map((cb) => cb.value);

        hidden.value = selected.length > 0 ? JSON.stringify(selected) : "";

        form.submit();
    });
});

// 4) EDIT REKENING
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".btn-edit-rekening").forEach((btn) => {
        btn.addEventListener("click", function () {
            const form = document.getElementById("formEditRekening");
            form.action = "/laravel/public/mrekening/" + this.dataset.id;

            document.getElementById("edit_nomor_rekening").value =
                this.dataset.nomor;
            document.getElementById("edit_bank").value = this.dataset.bank;
            document.getElementById("edit_atas_nama").value = this.dataset.atas;
            document.getElementById("edit_cabang").value = this.dataset.cabang;
        });
    });
});
