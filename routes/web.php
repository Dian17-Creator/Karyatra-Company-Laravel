<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MagendaController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ImportScheduleController;
use App\Http\Controllers\MscanController;
use App\Http\Controllers\MasterUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MscanManualController;
use App\Http\Controllers\MrequestController;
use App\Http\Controllers\MrequestExportController;
use App\Http\Controllers\MscanReportExportController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\SlipGajiController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\PayrollCalculationController;
use App\Http\Controllers\KirimSlipController;
use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\MasterRekeningController;
use App\Http\Controllers\FaceApprovalController;
use App\Http\Controllers\AdminDeviceController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\MscanForgotController;
use App\Http\Controllers\TdeptlokasiController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\MownerController;
use App\Mail\NotifikasiEmail;

Route::get("/", function () {
    return redirect("/login");
});

Route::get("/login", [LoginController::class, "showLoginForm"])->name("login");
Route::post("/login", [LoginController::class, "login"]);
Route::get("/register", [LoginController::class, "showRegisterForm"]);
Route::post("/register", [LoginController::class, "register"]);
Route::post("/logout", [LoginController::class, "logout"])->name("logout");

Route::get("/notifikasi/send-emails", [
    NotifikasiController::class,
    "sendEmails",
]);

Route::get("/slip/{filename}", function ($filename) {
    $path = public_path("karyatrahrd/slipgaji/" . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});

Route::middleware(["auth:web,owner"])->group(function () {
    // Backoffice (hanya admin-super)
    Route::get("/backoffice", [BackofficeController::class, "index"])->name(
        "backoffice.index",
    );
    Route::post("/backoffice/add", [
        BackofficeController::class,
        "storeUser",
    ])->name("backoffice.add");
    Route::post("/backoffice/delete-logs", [
        BackofficeController::class,
        "deleteLogs",
    ])->name("backoffice.deleteLogs");
    Route::post("/backoffice/delete-requests", [
        BackofficeController::class,
        "deleteRequests",
    ])->name("backoffice.deleteRequests");
    Route::get("/backoffice/logs/{id}", [
        BackofficeController::class,
        "viewLogs",
    ])->name("backoffice.viewLogs");
    Route::get("/backoffice/requests/{id}", [
        BackofficeController::class,
        "viewRequests",
    ])->name("backoffice.viewRequests");
    Route::get("/backoffice/requestcard/{id}", [
        BackofficeController::class,
        "viewRequestcard",
    ])->name("backoffice.viewRequestcard");
    Route::post("/backoffice/add-department", [
        BackofficeController::class,
        "addDepartment",
    ])->name("backoffice.addDepartment");
    Route::put("/backoffice/update-department/{id}", [
        BackofficeController::class,
        "updateDepartment",
    ])->name("backoffice.updateDepartment");
    Route::post("/backoffice/delete-department", [
        BackofficeController::class,
        "deleteDepartment",
    ])->name("backoffice.deleteDepartment");
    Route::post("/backoffice/delete-department", [
        BackofficeController::class,
        "deleteDepartment",
    ])->name("backoffice.deleteDepartment");
    Route::put("/backoffice/updateUser/{id}", [
        BackofficeController::class,
        "updateUser",
    ])->name("backoffice.updateUser");
    Route::post("/attendance/import-fingerprint", [
        BackofficeController::class,
        "importFingerprint",
    ])->name("attendance.importFingerprint");

    // Schedule Management
    Route::get("/schedule", [ScheduleController::class, "index"])->name(
        "schedule.index",
    );
    Route::post("/schedule", [ScheduleController::class, "store"])->name(
        "schedule.store",
    );
    Route::put("/schedule/{id}", [ScheduleController::class, "update"]);
    Route::delete("/schedule/{id}", [
        ScheduleController::class,
        "destroy",
    ])->name("schedule.destroy");
    // Kontrak kerja
    // Kontrak kerja
    Route::post("/schedule/contract", [
        ScheduleController::class,
        "storeContract",
    ])->name("schedule.contract.store");
    Route::put("/schedule/contract/{id}", [
        ScheduleController::class,
        "updateContract",
    ])->name("schedule.contract.update");
    Route::delete("/schedule/contract/{id}", [
        ScheduleController::class,
        "destroyContract",
    ])->name("schedule.contract.destroy");

    Route::get("/schedule/contract/calendar", [
        ScheduleController::class,
        "contractCalendar",
    ]);

    Route::get("/schedule/contract/by-date", [
        ScheduleController::class,
        "contractByDate",
    ]);

    // Agenda (magenda)
    Route::post("/magenda", [MagendaController::class, "store"]);
    Route::get("/magenda", [MagendaController::class, "index"]);
    Route::get("/magenda/by-date/{date}", [MagendaController::class, "byDate"]);
    // update / delete endpoints used by calendar detail modal
    Route::put("/magenda/{id}", [MagendaController::class, "update"]);
    Route::post("/magenda/delete/{id}", [MagendaController::class, "destroy"]);

    // Assign schedule
    Route::post("/user-schedule", [
        ScheduleController::class,
        "assignSchedule",
    ])->name("schedule.assign");
    Route::post("/user-schedule/generate", [
        ScheduleController::class,
        "showAssignForm",
    ])->name("schedule.generate");

    // Attendance
    Route::get("/attendance", [AttendanceController::class, "index"])->name(
        "attendance.index",
    );
    Route::get("/attendance/report", [
        AttendanceController::class,
        "getAttendanceReport",
    ])->name("attendance.report");
    Route::get("/attendance/missing", [
        AttendanceController::class,
        "getMissingAttendance",
    ])->name("attendance.missing");

    // Import Schedule
    Route::get("/schedule/import-schedule", function () {
        return view("schedule.import-schedule");
    });
    Route::post("/schedule/file-import-schedule", [
        ImportScheduleController::class,
        "importSchedule",
    ])->name("file-import-schedule");

    //Export Schedule
    Route::get("/schedule/export", [
        ScheduleController::class,
        "exportSchedule",
    ]);

    // Export Mscan
    Route::get("/export-mscan", [MscanController::class, "exportExcel"])->name(
        "export-mscan",
    );

    // Master User
    Route::resource("masteruser", MasterUserController::class)->middleware(
        "superadmin",
    );

    // Approval Absensi
    Route::post("/mscan/approve/captain/{id}", [
        MscanManualController::class,
        "approveCaptain",
    ])->name("mscan.approve.captain");
    Route::post("/mscan/approve/super/{id}", [
        MscanManualController::class,
        "approveSupervisor",
    ])->name("mscan.approve.super");
    Route::post("/mscan/approve/hrd/{id}", [
        MscanManualController::class,
        "approveHrd",
    ])->name("mscan.approve.hrd");

    // Approval Izin
    Route::post("/mrequest/captain/{id}/approve", [
        MrequestController::class,
        "approveCaptain",
    ])->name("mrequest.approve.captain");

    Route::post("/mrequest/supervisor/{id}/approve", [
        MrequestController::class,
        "approveSupervisor",
    ])->name("mrequest.approve.super");

    Route::post("/mrequest/hrd/{id}/approve", [
        MrequestController::class,
        "approveHrd",
    ])->name("mrequest.approve.hrd");

    Route::get("/export-request", [
        MrequestExportController::class,
        "export",
    ])->name("export-request");

    Route::get("/export-attendance-report", [
        MscanReportExportController::class,
        "export",
    ])->name("export-attendance-report");

    Route::get("/notifikasi", [NotifikasiController::class, "index"])->name(
        "notifikasi.index",
    );

    Route::get("/test-email", function () {
        $data = [
            ["message" => "Tes kirim email Laravel via Gmail", "time" => now()],
        ];

        try {
            Mail::to("matahati.hrd@gmail.com")->send(
                new NotifikasiEmail($data),
            );
            return "✅ Email berhasil dikirim!";
        } catch (\Exception $e) {
            return "❌ Gagal kirim email: " . $e->getMessage();
        }
    });

    Route::post("/backoffice/slip/import", [
        SlipGajiController::class,
        "importAndSend",
    ])
        ->name("backoffice.importSlips")
        ->middleware("auth:web,owner");

    // 🔹 Detail Izin (mrequest)
    Route::get("/izin/{id}", [mrequestController::class, "show"])->name(
        "izin.show",
    );

    // 🔹 Detail Absen Manual (mscan_manual)
    Route::get("/absen/manual/{id}", [
        MscanManualController::class,
        "show",
    ])->name("absen.manual.show");

    // 🔹 Detail Pegawai (masteruser)
    Route::get("/pegawai/{id}", [MasterUserController::class, "show"])->name(
        "pegawai.show",
    );

    // 🔹 Halaman Penggajian
    Route::prefix("penggajian")->group(function () {
        Route::get("/", [GajiController::class, "index"])->name(
            "penggajian.index",
        );
        Route::put("/update/{id}", [GajiController::class, "update"])->name(
            "gaji.update",
        ); // ✔ BENAR
        Route::post("/recalc/{userId}", [
            GajiController::class,
            "recalcUser",
        ])->name("gaji.recalc.user");
        Route::post("/recalc-all", [
            PayrollCalculationController::class,
            "recalcAll",
        ])->name("gaji.recalc.all");
        Route::post("/approve/{id}", [GajiController::class, "approve"])->name(
            "gaji.approve",
        );
        Route::post("/lock/{id}", [GajiController::class, "lock"])->name(
            "gaji.lock",
        );
        Route::delete("/delete/{id}", [GajiController::class, "destroy"])->name(
            "gaji.delete",
        );
        // Tunjangan
        Route::get("/tunjangan", [
            GajiController::class,
            "tunjanganIndex",
        ])->name("tunjangan.index");
        Route::post("/tunjangan", [
            GajiController::class,
            "tunjanganStore",
        ])->name("tunjangan.store");
        Route::delete("/tunjangan/{id}", [
            GajiController::class,
            "tunjanganDelete",
        ])->name("tunjangan.delete");
        Route::get("/tunjangan/latest/{nid}", [
            GajiController::class,
            "getLatestTunjangan",
        ])->name("tunjangan.latest");
        // web.php
        Route::post("/gaji/kirim", [
            KirimSlipController::class,
            "kirimSlip",
        ])->name("gaji.kirim");
        Route::get("/gaji/preview-slip/{id}", [
            KirimSlipController::class,
            "previewSlip",
        ])->name("gaji.preview.slip");
        Route::post("/kirim-slip-single", [
            KirimSlipController::class,
            "kirimSlipSingle",
        ])->name("penggajian.kirim-slip-single");
        Route::post("/penggajian/export-by-department", [
            GajiController::class,
            "exportByDepartment",
        ])->name("gaji.exportByDepartment");

        Route::get("/gaji/get-info/{id}", [
            GajiController::class,
            "getSlipInfo",
        ]);
    });

    Route::post("/penggajian/export", [
        PayrollExportController::class,
        "exportExcel",
    ])->name("gaji.export");

    Route::post("/gaji/export-bank", [
        PayrollExportController::class,
        "exportBank",
    ])->name("payroll.export.bank");

    Route::get("/export-report", [
        PayrollExportController::class,
        "exportReport",
    ])->name("payroll.export.report");

    Route::get("/export-kehadiran", [
        PayrollExportController::class,
        "exportKehadiran",
    ])->name("kehadiran.export.report");

    Route::get("/payroll/mandiri/excel", [
        PayrollExportController::class,
        "exportMandiriExcel",
    ])->name("payroll.mandiri.excel");

    Route::get("/payroll/mandiri/csv", [
        PayrollExportController::class,
        "exportMandiriCsv",
    ])->name("payroll.mandiri.csv");

    Route::post("payroll/recalc-ajax", [
        PayrollCalculationController::class,
        "recalcAjax",
    ])->name("payroll.recalc.ajax");

    Route::post("/penggajian/recalc-ajax", [
        PayrollCalculationController::class,
        "recalcAjax",
    ]);

    Route::get("/penggajian/filter-department", [
        App\Http\Controllers\GajiController::class,
        "filterByDepartment",
    ])->name("penggajian.filter.department");

    // dalam middleware auth group dan prefix penggajian
    Route::get("/mrekening", [MasterRekeningController::class, "index"])->name(
        "mrekening.index",
    );
    Route::post("/mrekening", [MasterRekeningController::class, "store"])->name(
        "mrekening.store",
    );
    Route::put("/mrekening/{id}", [
        MasterRekeningController::class,
        "update",
    ])->name("mrekening.update");
    Route::delete("/mrekening/{id}", [
        MasterRekeningController::class,
        "destroy",
    ])->name("mrekening.destroy");
    Route::get("/mrekening/by-bank/{bank}", [
        MasterRekeningController::class,
        "byBank",
    ])->name("mrekening.byBank");

    // Face Approval Routes
    Route::get("/hr/face-approval", [
        FaceApprovalController::class,
        "index",
    ])->name("hr.face_approval.index");

    Route::post("/hr/face-approval/{id}/approve", [
        FaceApprovalController::class,
        "approve",
    ])->name("hr.face_approval.approve");

    Route::post("/hr/face-approval/{id}/reject", [
        FaceApprovalController::class,
        "reject",
    ])->name("hr.face_approval.reject");

    Route::get("/hr/face/{id}", [FaceApprovalController::class, "show"])->name(
        "hr.face_approval.show",
    );

    // Device Id routes
    Route::post("/admin-devices", [
        AdminDeviceController::class,
        "store",
    ])->name("admin-devices.store");
    Route::post("/admin-devices/{id}/approve", [
        AdminDeviceController::class,
        "approve",
    ])->name("admin-devices.approve");
    Route::post("/admin-devices/{id}/reject", [
        AdminDeviceController::class,
        "reject",
    ])->name("admin-devices.reject");
    Route::post("/admin-devices/{id}/toggle", [
        AdminDeviceController::class,
        "toggle",
    ])->name("admin-devices.toggle");
    Route::delete("/admin-devices/{id}", [
        AdminDeviceController::class,
        "destroy",
    ])->name("admin-devices.destroy");

    //Export Daftar Users
    Route::get("/backoffice/users/export/excel", [
        UserExportController::class,
        "exportExcel",
    ])->name("backoffice.users.export.excel");

    Route::get("/backoffice/users/export/pdf", [
        UserExportController::class,
        "exportPdf",
    ])->name("backoffice.users.export.pdf");

    Route::get("/salary/resend/{uid}/{year}/{month}", [
        PayrollCalculationController::class,
        "resendEmail",
    ])->name("salary.resend");

    // Mowner Management
    Route::resource('mowner', MownerController::class);

    Route::resource("tdeptlokasi", TdeptlokasiController::class);
});
