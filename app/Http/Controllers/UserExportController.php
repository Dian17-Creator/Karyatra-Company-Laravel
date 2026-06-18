<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Models\muser;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class UserExportController extends Controller
{
    /**
     * Export daftar user ke Excel
     * (data tabel, tanpa gambar embed)
     */
    public function exportExcel()
    {
        if (auth()->user()->fhrd != 1) {
            abort(403, "Anda tidak memiliki izin untuk export data user.");
        }

        return Excel::download(
            new UserExport(),
            "daftar_user_" . now()->format("Ymd_His") . ".xlsx",
        );
    }

    /**
     * Export daftar user ke PDF
     * (lengkap + foto wajah)
     */
    public function exportPdf()
    {
        if (auth()->user()->fhrd != 1) {
            abort(403);
        }

        $authUser = auth()->user();
        $query = muser::with(["faces", "department"])
            ->orderBy("cname");

        if ($authUser && $authUser->ccompany) {
            $query->where('ccompany', $authUser->ccompany);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $photos = []; // ⬅️ array biasa

            foreach ($user->faces as $face) {
                //$path = $_SERVER['DOCUMENT_ROOT'] . '/faces/' . $face->cfilename;
                public_path("karyatrahrd/biometrik/" . $face->cfilename);

                if (is_file($path)) {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $photos[] =
                        "data:image/" .
                        $ext .
                        ";base64," .
                        base64_encode(file_get_contents($path));
                }
            }

            // ⬅️ SET SEKALI
            $user->setAttribute("photos", $photos);
        }

        return Pdf::loadView("exports.user_pdf", compact("users"))
            ->setPaper("A4", "landscape")
            ->download("daftar_user_" . now()->format("Ymd_His") . ".pdf");
    }
}
