<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Csalary;
use App\Models\Tusercontract;
use App\Models\muser;
use App\Models\Mtunjangan;
use App\Jobs\CalculatePayrollJob;
use Illuminate\Http\Request;
use App\Mail\SlipKirimGaji;
use Carbon\Carbon;
use DB;

class KirimSlipController extends Controller
{
    public function kirimSlip(Request $req)
    {
        $ids = json_decode($req->ids, true);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(
                ["success" => false, "message" => "No IDs provided."],
                400,
            );
        }

        $uploadDir = public_path("hrd/slipgaji");
        if (!File::exists($uploadDir)) {
            try {
                File::makeDirectory($uploadDir, 0755, true);
            } catch (\Exception $e) {
                Log::error(
                    "kirimSlip: gagal membuat folder {$uploadDir} - " .
                        $e->getMessage(),
                );
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Gagal membuat folder penyimpanan slip.",
                    ],
                    500,
                );
            }
        }

        $summary = [
            "total" => count($ids),
            "sent" => 0,
            "failed" => 0,
            "items" => [],
        ];

        foreach ($ids as $id) {
            $itemResult = [
                "id" => $id,
                "ok" => false,
                "email" => null,
                "wa" => null,
                "errors" => [],
            ];

            $row = Csalary::with("user")->find($id);
            if (!$row) {
                $msg = "Csalary id={$id} tidak ditemukan";
                Log::warning("kirimSlip: {$msg}");
                $itemResult["errors"][] = $msg;
                $summary["failed"]++;
                $summary["items"][] = $itemResult;
                continue;
            }

            $user = $row->user;
            if (!$user) {
                $msg = "user not found for csalary id={$id}";
                Log::warning("kirimSlip: {$msg}");
                $itemResult["errors"][] = $msg;
                $summary["failed"]++;
                $summary["items"][] = $itemResult;
                continue;
            }

            // compute month label (reuse your existing logic)
            try {
                $periodMonth = $row->period_month ?? ($row->month ?? null);
                $periodYear = $row->period_year ?? ($row->year ?? null);
                $bulanFormatted = "";

                if (!empty($periodMonth) && !empty($periodYear)) {
                    $m = (int) $periodMonth;
                    $y = (int) $periodYear;
                    if ($m >= 1 && $m <= 12) {
                        $bulanFormatted = Carbon::createFromDate(
                            $y,
                            $m,
                            1,
                        )->translatedFormat("F Y");
                    }
                }

                if (empty($bulanFormatted)) {
                    $candidates = [
                        $row->period ?? null,
                        $row->periode ?? null,
                        $row->period_text ?? null,
                        $row->date ?? null,
                        $row->tanggal ?? null,
                        $row->created_at ?? null,
                    ];
                    foreach ($candidates as $cand) {
                        if (empty($cand)) {
                            continue;
                        }
                        try {
                            $dt = Carbon::parse($cand);
                            $bulanFormatted = $dt->translatedFormat("F Y");
                            break;
                        } catch (\Exception $e) {
                            // continue
                        }
                    }
                }

                if (empty($bulanFormatted)) {
                    $bulanFormatted = Carbon::now()->translatedFormat("F Y");
                }
                $row->bulan = $bulanFormatted;
            } catch (\Exception $e) {
                Log::warning(
                    "kirimSlip: gagal menentukan bulan untuk csalary id={$id} - " .
                        $e->getMessage(),
                );
                $row->bulan = Carbon::now()->translatedFormat("F Y");
            }

            // prepare data for PDF view
            $pdfData = [
                "nama" => $user->cfullname ?? "-",
                "jabatan" => $row->jabatan ?? "-",
                "hari_masuk" => $row->jumlah_masuk ?? 0,
                "bulan" => $row->bulan ?? "",
                "gaji_pokok" => $row->gaji_pokok ?? 0,
                "tunjangan_makan" => $row->tunjangan_makan ?? 0,
                "tunjangan_jabatan" => $row->tunjangan_jabatan ?? 0,
                "tunjangan_transport" => $row->tunjangan_transport ?? 0,
                "tunjangan_luar_kota" => $row->tunjangan_luar_kota ?? 0,
                "tunjangan_backup" => $row->tunjangan_backup ?? 0,
                "lembur" => $row->gaji_lembur ?? 0,
                "tabungan_diambil" => $row->tabungan_diambil ?? 0,
                "potongan_lain" => $row->potongan_lain ?? 0,
                "potongan_tabungan" => $row->potongan_tabungan ?? 0,
                "jumlah_penghasilan" =>
                    ($row->gaji_pokok ?? 0) +
                    ($row->tunjangan_makan ?? 0) +
                    ($row->tunjangan_jabatan ?? 0) +
                    ($row->tunjangan_transport ?? 0) +
                    ($row->tunjangan_luar_kota ?? 0) +
                    ($row->tunjangan_backup ?? 0) +
                    ($row->gaji_lembur ?? 0) +
                    ($row->tabungan_diambil ?? 0),
                "jumlah_potongan" =>
                    ($row->potongan_lain ?? 0) + ($row->potongan_tabungan ?? 0),
                "gaji_diterima" => $row->total_gaji ?? 0,
                "catatan" => $row->note ?? "",
            ];

            try {
                // make filename unique per user+model-time to avoid reuse/caching issues
                $slug = Str::slug($user->cname ?? "user");
                $uniqueSuffix = md5(
                    $row->updated_at ?? ($row->created_at ?? now()),
                );
                $filename =
                    "SlipGaji_{$slug}_" .
                    ($row->period_year ?? date("Y")) .
                    str_pad(
                        $row->period_month ?? date("n"),
                        2,
                        "0",
                        STR_PAD_LEFT,
                    ) .
                    "_{$uniqueSuffix}.pdf";
                $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                // generate PDF binary fresh for each user
                $pdfBinary = Pdf::loadView("pdfs.slip_gaji", $pdfData)
                    ->setPaper("A4", "portrait")
                    ->output();

                // write file
                File::put($filePath, $pdfBinary);
                clearstatcache(true, $filePath);

                $itemResult["file"] = $filePath;
                $itemResult["file_size"] = File::exists($filePath)
                    ? File::size($filePath)
                    : null;
            } catch (\Exception $e) {
                $msg =
                    "gagal generate atau simpan PDF untuk csalary id={$id} - " .
                    $e->getMessage();
                Log::error("kirimSlip: {$msg}");
                $itemResult["errors"][] = $msg;
                $summary["failed"]++;
                $summary["items"][] = $itemResult;
                // continue to next user
                continue;
            }

            // send email (if requested)
            if (
                $req->send_email == 1 &&
                filter_var($user->cmailaddress, FILTER_VALIDATE_EMAIL)
            ) {
                try {
                    // use your Mailable which should attach the file
                    Mail::to($user->cmailaddress)->send(
                        new \App\Mail\SlipKirimGaji($row, $filePath),
                    );
                    $itemResult["email"] = "sent";
                    Log::info(
                        "kirimSlip: email terkirim ke {$user->cmailaddress} for csalary id={$id}",
                    );
                } catch (\Exception $e) {
                    $err =
                        "gagal kirim email ke {$user->cmailaddress} - " .
                        $e->getMessage();
                    Log::error("kirimSlip: {$err}");
                    $itemResult["email"] = "failed";
                    $itemResult["errors"][] = $err;
                    // do not abort loop
                }
            } else {
                $itemResult["email"] = "skipped";
            }

            // send WA (if requested)
            if ($req->send_wa == 1) {
                try {
                    $wa = $this->sendWhatsAppSlip(
                        $user,
                        $filename,
                        $row->bulan,
                    );
                    $itemResult["wa"] = $wa;
                    if (!empty($wa["success"])) {
                        Log::info(
                            "kirimSlip: WA terkirim ke {$user->cphone} for csalary id={$id}",
                        );
                    } else {
                        Log::warning(
                            "kirimSlip: WA gagal ke {$user->cphone} for csalary id={$id} - " .
                                ($wa["msg"] ?? "no-msg"),
                        );
                        $itemResult["errors"][] = "wa: " . json_encode($wa);
                    }
                } catch (\Exception $e) {
                    $err =
                        "gagal proses WA ke {$user->cphone} - " .
                        $e->getMessage();
                    Log::error("kirimSlip: {$err}");
                    $itemResult["wa"] = "failed";
                    $itemResult["errors"][] = $err;
                }
            } else {
                $itemResult["wa"] = "skipped";
            }

            // mark success if at least one channel succeeded (email or wa) - adjust rule as needed
            $ok = false;
            if ($req->send_email == 1 && $itemResult["email"] === "sent") {
                $ok = true;
            }
            if (
                $req->send_wa == 1 &&
                is_array($itemResult["wa"]) &&
                !empty($itemResult["wa"]["success"])
            ) {
                $ok = true;
            }
            if ($req->send_email == 0 && $req->send_wa == 0) {
                // nothing to send but file created -> consider as success
                $ok = true;
            }

            $itemResult["ok"] = $ok;
            if ($ok) {
                $summary["sent"]++;
            } else {
                $summary["failed"]++;
            }

            // store public URL for reference
            $appUrl = rtrim(config("app.url") ?: env("APP_URL", ""), "/");
            //            $itemResult['url'] = "https://absensi.matahati.my.id/laravel/public/uploads/slipgaji/{$filename}";
            $itemResult["url"] = url("hrd/slipgaji/" . $filename);

            // free some memory
            if (isset($pdfBinary)) {
                unset($pdfBinary);
            }

            $summary["items"][] = $itemResult;
        } // end foreach

        $finalMsg = "Selesai. Terkirim: {$summary["sent"]}, Gagal: {$summary["failed"]}";
        Log::info("kirimSlip: finished - {$finalMsg}");

        return response()->json([
            "success" => true,
            "message" => $finalMsg,
            "summary" => $summary,
        ]);
    }

    public function previewSlip($id)
    {
        $row = Csalary::with("user")->findOrFail($id);
        $user = $row->user;

        // ---------- Hitung / format bulan dan simpan ke $row->bulan ----------
        try {
            $periodMonth = $row->period_month ?? ($row->month ?? null);
            $periodYear = $row->period_year ?? ($row->year ?? null);
            $bulanFormatted = "";

            if (!empty($periodMonth) && !empty($periodYear)) {
                $m = (int) $periodMonth;
                $y = (int) $periodYear;
                if ($m >= 1 && $m <= 12) {
                    $bulanFormatted = Carbon::createFromDate(
                        $y,
                        $m,
                        1,
                    )->translatedFormat("F Y");
                }
            }

            if (empty($bulanFormatted)) {
                $candidates = [
                    $row->period ?? null,
                    $row->periode ?? null,
                    $row->period_text ?? null,
                    $row->date ?? null,
                    $row->tanggal ?? null,
                    $row->created_at ?? null,
                ];
                foreach ($candidates as $cand) {
                    if (empty($cand)) {
                        continue;
                    }
                    try {
                        $dt = Carbon::parse($cand);
                        $bulanFormatted = $dt->translatedFormat("F Y");
                        break;
                    } catch (\Exception $e) {
                        // ignore and try next
                    }
                }
            }

            if (empty($bulanFormatted)) {
                $bulanFormatted = Carbon::now()->translatedFormat("F Y");
            }

            $row->bulan = $bulanFormatted;
        } catch (\Exception $e) {
            Log::warning(
                "previewSlip: gagal menentukan bulan untuk csalary id={$id} - " .
                    $e->getMessage(),
            );
            $row->bulan = Carbon::now()->translatedFormat("F Y");
        }

        $data = [
            "nama" => $user->cfullname ?? "-",
            "jabatan" => $row->jabatan ?? "-",
            "hari_masuk" => $row->jumlah_masuk ?? 0,
            "bulan" =>
                $row->bulan ??
                ($row->period_month ?? "") . " " . ($row->period_year ?? ""),
            "gaji_pokok" => $row->gaji_pokok ?? 0,
            "tunjangan_makan" => $row->tunjangan_makan ?? 0,
            "tunjangan_jabatan" => $row->tunjangan_jabatan ?? 0,
            "tunjangan_transport" => $row->tunjangan_transport ?? 0,
            "tunjangan_luar_kota" => $row->tunjangan_luar_kota ?? 0,
            "tunjangan_backup" => $row->tunjangan_backup ?? 0,
            "lembur" => $row->gaji_lembur ?? 0,
            "tabungan_diambil" => $row->tabungan_diambil ?? 0,
            "potongan_lain" => $row->potongan_lain ?? 0,
            "potongan_tabungan" => $row->potongan_tabungan ?? 0,
            "jumlah_penghasilan" =>
                ($row->gaji_pokok ?? 0) +
                ($row->tunjangan_makan ?? 0) +
                ($row->tunjangan_jabatan ?? 0) +
                ($row->tunjangan_transport ?? 0) +
                ($row->tunjangan_luar_kota ?? 0) +
                ($row->tunjangan_backup ?? 0) +
                ($row->gaji_lembur ?? 0) +
                ($row->tabungan_diambil ?? 0),
            "jumlah_potongan" =>
                ($row->potongan_lain ?? 0) + ($row->potongan_tabungan ?? 0),
            "gaji_diterima" => $row->total_gaji ?? 0,
            "catatan" => $row->note ?? "",
        ];

        try {
            $pdf = Pdf::loadView("pdfs.slip_gaji", $data);
            return $pdf->stream("preview_slip_{$id}.pdf");
        } catch (\Exception $e) {
            Log::error(
                "previewSlip: gagal generate PDF id={$id} - " .
                    $e->getMessage(),
            );
            abort(500, "Gagal generate preview slip.");
        }
    }

    // Paste ke dalam KirimSlipController — ganti kirimSlipSingle yang lama
    public function kirimSlipSingle(Request $req)
    {
        $id = $req->input("id");
        if (empty($id)) {
            return response()->json(
                ["success" => false, "message" => "Missing id"],
                400,
            );
        }

        $row = Csalary::with("user")->find($id);
        if (!$row) {
            Log::warning("kirimSlipSingle: csalary id={$id} not found");
            return response()->json(
                ["success" => false, "message" => "Data tidak ditemukan"],
                404,
            );
        }

        $user = $row->user;
        if (!$user) {
            Log::warning(
                "kirimSlipSingle: user not found for csalary id={$id}",
            );
            return response()->json(
                ["success" => false, "message" => "User tidak ditemukan"],
                404,
            );
        }

        $uploadDir = public_path("hrd/slipgaji");
        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        // compute bulan (reuse logic)
        try {
            $periodMonth = $row->period_month ?? ($row->month ?? null);
            $periodYear = $row->period_year ?? ($row->year ?? null);
            $bulanFormatted = "";

            if (!empty($periodMonth) && !empty($periodYear)) {
                $m = (int) $periodMonth;
                $y = (int) $periodYear;
                if ($m >= 1 && $m <= 12) {
                    $bulanFormatted = \Carbon\Carbon::createFromDate(
                        $y,
                        $m,
                        1,
                    )->translatedFormat("F Y");
                }
            }

            if (empty($bulanFormatted)) {
                $candidates = [
                    $row->period ?? null,
                    $row->periode ?? null,
                    $row->date ?? null,
                    $row->created_at ?? null,
                ];
                foreach ($candidates as $cand) {
                    if (empty($cand)) {
                        continue;
                    }
                    try {
                        $dt = \Carbon\Carbon::parse($cand);
                        $bulanFormatted = $dt->translatedFormat("F Y");
                        break;
                    } catch (\Exception $e) {
                        // continue
                    }
                }
            }

            if (empty($bulanFormatted)) {
                $bulanFormatted = \Carbon\Carbon::now()->translatedFormat(
                    "F Y",
                );
            }
            $row->bulan = $bulanFormatted;
        } catch (\Exception $e) {
            $row->bulan = \Carbon\Carbon::now()->translatedFormat("F Y");
        }

        // prepare data untuk view PDF
        $pdfData = [
            "nama" => $user->cfullname ?? "-",
            "jabatan" => $row->jabatan ?? ($user->jabatan->name ?? "-"),
            "hari_masuk" => $row->jumlah_masuk ?? 0,
            "bulan" => $row->bulan ?? "",
            "gaji_pokok" => $row->gaji_pokok ?? 0,
            "tunjangan_makan" => $row->tunjangan_makan ?? 0,
            "tunjangan_jabatan" => $row->tunjangan_jabatan ?? 0,
            "tunjangan_transport" => $row->tunjangan_transport ?? 0,
            "tunjangan_luar_kota" => $row->tunjangan_luar_kota ?? 0,
            "tunjangan_backup" => $row->tunjangan_backup ?? 0,
            "lembur" => $row->gaji_lembur ?? 0,
            "tabungan_diambil" => $row->tabungan_diambil ?? 0,
            "potongan_lain" => $row->potongan_lain ?? 0,
            "potongan_tabungan" => $row->potongan_tabungan ?? 0,
            "jumlah_penghasilan" =>
                ($row->gaji_pokok ?? 0) +
                ($row->tunjangan_makan ?? 0) +
                ($row->tunjangan_jabatan ?? 0) +
                ($row->tunjangan_transport ?? 0) +
                ($row->tunjangan_luar_kota ?? 0) +
                ($row->tunjangan_backup ?? 0) +
                ($row->gaji_lembur ?? 0) +
                ($row->tabungan_diambil ?? 0),
            "jumlah_potongan" =>
                ($row->potongan_lain ?? 0) + ($row->potongan_tabungan ?? 0),
            "gaji_diterima" => $row->total_gaji ?? 0,
            "catatan" => $row->note ?? "",
        ];

        try {
            // decide whether to regenerate file or reuse
            $slug = Str::slug($user->cname ?? "user");
            // make filename semi-unique with updated_at hash to avoid stale caching
            $uniqueSuffix = md5(
                $row->updated_at ?? ($row->created_at ?? now()),
            );
            $filename =
                "SlipGaji_" .
                $slug .
                "_" .
                ($row->period_year ?? date("Y")) .
                str_pad($row->period_month ?? date("n"), 2, "0", STR_PAD_LEFT) .
                "_{$uniqueSuffix}.pdf";
            $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            $needRegenerate = true;
            if (File::exists($filePath)) {
                if ($req->input("force_regen") == 1) {
                    $needRegenerate = true;
                } else {
                    try {
                        $fileMTime = File::lastModified($filePath);
                        $modelTime = strtotime(
                            $row->updated_at ?? ($row->created_at ?? now()),
                        );
                        $needRegenerate = $modelTime > $fileMTime;
                    } catch (\Exception $e) {
                        $needRegenerate = true;
                    }
                }
            }

            // If file exists and up-to-date -> reuse (but still allow send)
            if (!$needRegenerate && File::exists($filePath)) {
                Log::info(
                    "kirimSlipSingle: file exists and up-to-date, will reuse csalary id={$id} file={$filename}",
                );

                if (
                    $req->input("send_email") == 1 &&
                    filter_var($user->cmailaddress, FILTER_VALIDATE_EMAIL)
                ) {
                    try {
                        // log before attach
                        clearstatcache(true, $filePath);
                        Log::info("kirimSlipSingle: (re)attach existing file", [
                            "file" => $filePath,
                            "size" => File::exists($filePath)
                                ? File::size($filePath)
                                : null,
                        ]);

                        try {
                            Mail::to($user->cmailaddress)->send(
                                new \App\Mail\SlipKirimGaji($row, $filePath),
                            );
                            Log::info(
                                "kirimSlipSingle: (re)email sent to {$user->cmailaddress} using existing file",
                            );
                        } catch (\Exception $e) {
                            Log::error(
                                "kirimSlipSingle: mail error to {$user->cmailaddress} - " .
                                    $e->getMessage(),
                            );
                        }

                        Log::info(
                            "kirimSlipSingle: (re)email sent to {$user->cmailaddress} using existing file",
                        );
                    } catch (\Exception $e) {
                        Log::error(
                            "kirimSlipSingle: mail error to {$user->cmailaddress} - " .
                                $e->getMessage(),
                        );
                    }
                }

                $waResult = null;
                if ($req->input("send_wa") == 1) {
                    $waResult = $this->sendWhatsAppSlip(
                        $user,
                        $filename,
                        $row->bulan,
                    );
                    Log::info(
                        "kirimSlipSingle: WA result for user_id={$user->nid}",
                        is_array($waResult)
                            ? $waResult
                            : ["raw" => (string) $waResult],
                    );
                }

                $pdfUrl =
                    rtrim(config("app.url") ?: env("APP_URL", ""), "/") .
                    "/hrd/slipgaji/{$filename}";
                return response()->json(
                    [
                        "success" => true,
                        "message" =>
                            "File sudah ada dan up-to-date, dikirim menggunakan file yang ada.",
                        "filename" => $filename,
                        "skipped" => true,
                        "url" => $pdfUrl,
                        "wa_result" => $waResult ?? null,
                    ],
                    200,
                );
            }

            // generate new PDF binary
            Log::info(
                "kirimSlipSingle: generating pdf for csalary id={$id}",
                $pdfData,
            );
            $pdfBinary = Pdf::loadView("pdfs.slip_gaji", $pdfData)
                ->setPaper("A4", "portrait")
                ->output();

            $pdfBinaryMd5 = md5($pdfBinary);

            // save file
            File::put($filePath, $pdfBinary);
            clearstatcache(true, $filePath);
            $writtenExists = File::exists($filePath);
            $writtenSize = $writtenExists ? File::size($filePath) : null;
            $writtenMd5 = $writtenExists ? md5_file($filePath) : null;

            Log::info(
                "kirimSlipSingle: saved {$filePath} for user_id={$user->nid}",
                [
                    "binary_md5" => $pdfBinaryMd5,
                    "file_exists" => $writtenExists,
                    "file_size" => $writtenSize,
                    "file_md5" => $writtenMd5,
                    "file_mtime" => $writtenExists
                        ? date("c", File::lastModified($filePath))
                        : null,
                ],
            );

            if ($writtenMd5 !== $pdfBinaryMd5) {
                Log::warning(
                    "kirimSlipSingle: MD5 mismatch between generated binary and written file",
                    [
                        "binary_md5" => $pdfBinaryMd5,
                        "file_md5" => $writtenMd5,
                    ],
                );
            }

            // send email if requested
            if (
                $req->input("send_email") == 1 &&
                filter_var($user->cmailaddress, FILTER_VALIDATE_EMAIL)
            ) {
                try {
                    Log::info("kirimSlipSingle: attaching file to email", [
                        "file" => $filePath,
                        "size" => $writtenSize,
                    ]);
                    Mail::send([], [], function ($message) use (
                        $user,
                        $filePath,
                        $filename,
                    ) {
                        $message
                            ->to($user->cmailaddress)
                            ->subject("Slip Gaji Mata Hati Café")
                            ->setBody(
                                "Halo {$user->cname}, terlampir slip gaji Anda.",
                                "text/html",
                            )
                            ->attach($filePath, [
                                "as" => $filename,
                                "mime" => "application/pdf",
                            ]);
                    });
                    Log::info(
                        "kirimSlipSingle: email sent to {$user->cmailaddress}",
                    );
                } catch (\Exception $e) {
                    Log::error(
                        "kirimSlipSingle: mail error to {$user->cmailaddress} - " .
                            $e->getMessage(),
                    );
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "Gagal mengirim email",
                            "error" => $e->getMessage(),
                        ],
                        500,
                    );
                }
            }

            // send WA if requested
            if ($req->input("send_wa") == 1) {
                $wa = $this->sendWhatsAppSlip($user, $filename, $row->bulan);
                if (!empty($wa["success"])) {
                    Log::info(
                        "kirimSlipSingle: WA terkirim ke {$user->cphone}",
                    );
                } else {
                    Log::warning(
                        "kirimSlipSingle: WA gagal untuk {$user->cphone}",
                        is_array($wa) ? $wa : ["raw" => (string) $wa],
                    );
                }
            }

            $pdfUrl =
                rtrim(config("app.url") ?: env("APP_URL", ""), "/") .
                "/hrd/slipgaji/{$filename}";
            return response()->json(
                [
                    "success" => true,
                    "message" => "Terkirim",
                    "filename" => $filename,
                    "url" => $pdfUrl,
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error(
                "kirimSlipSingle: error processing id={$id} - " .
                    $e->getMessage(),
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => "Gagal generate/simpan slip",
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // Paste ke dalam KirimSlipController — ganti sendWhatsAppSlip yang lama
    private function sendWhatsAppSlip($user, $filename, $bulan)
    {
        try {
            // ----- nomor HP -----
            $phoneRaw = trim((string) ($user->cphone ?? ""));
            if (empty($phoneRaw)) {
                Log::warning("WA: nomor HP kosong untuk user {$user->cname}");
                return ["success" => false, "msg" => "Nomor HP kosong"];
            }

            // hanya digit
            $clean = preg_replace("/[^0-9]/", "", $phoneRaw);

            // normalisasi: +62 rules
            if (strpos($clean, "0") === 0) {
                $clean = "62" . substr($clean, 1);
            } elseif (strpos($clean, "8") === 0) {
                $clean = "62" . $clean;
            } elseif (strpos($clean, "62") === 0) {
                // ok
            } else {
                $clean = "62" . $clean;
            }

            // ----- cek kredensial Watzap -----
            $apiKey = env("WATZAP_API_KEY");
            $numberKey = env("WATZAP_NUMBER_KEY");

            if (empty($apiKey) || empty($numberKey)) {
                Log::error("WA: API KEY atau NUMBER KEY belum diset di .env");
                return ["success" => false, "msg" => "API Key missing"];
            }

            // ----- path file & url publik -----
            $uploadPath = public_path("hrd/slipgaji/" . $filename);

            // pastikan file tersedia di disk (best-effort check)
            clearstatcache(true, $uploadPath);
            $fileExists = File::exists($uploadPath);
            $fileSize = $fileExists ? File::size($uploadPath) : null;
            $fileMTime = $fileExists
                ? date("c", File::lastModified($uploadPath))
                : null;

            $appUrl = rtrim(config("app.url") ?: env("APP_URL", ""), "/");
            //$pdfUrl = "https://absensi.matahati.my.id/laravel/public/uploads/slipgaji/{$filename}";
            $pdfUrl = url("hrd/slipgaji/" . $filename);

            Log::info(
                "WA: preparing send -> user_id={$user->nid} name={$user->cname} phone_raw={$phoneRaw} phone_clean={$clean} filename={$filename} file_exists=" .
                    ($fileExists ? "1" : "0") .
                    " size=" .
                    ($fileSize ?? "n/a") .
                    " mtime=" .
                    ($fileMTime ?? "n/a") .
                    " pdfUrl={$pdfUrl}",
            );

            // quick-check file reachable (best-effort)
            try {
                $headers = @get_headers($pdfUrl);
                if (
                    !is_array($headers) ||
                    stripos($headers[0] ?? "", "200") === false
                ) {
                    Log::warning(
                        "WA: PDF not reachable at {$pdfUrl} (headers=" .
                            json_encode($headers) .
                            ")",
                    );
                } else {
                    Log::info("WA: PDF reachable at {$pdfUrl}");
                }
            } catch (\Throwable $e) {
                Log::warning(
                    "WA: get_headers failed for {$pdfUrl} - " .
                        $e->getMessage(),
                );
            }

            $footer = env("WA_FOOTER");

            // ----- prepare caption -----
            $caption =
                "Halo rekan-rekan,\n\n" .
                "Berikur terlampir slip gaji untuk bulan {$bulan}. Mohon untuk dicek terlebih dahulu, dan apabila terdapat ketidaksesuaian, silakan konfirmasi paling lambat hari ini.\n\n" .
                "Terima kasih atas perhatian dan kerja samanya.\n\n" .
                "_{$footer}_";

            // ----- kirim teks dulu (send_message) -----
            $textData = [
                "api_key" => $apiKey,
                "number_key" => $numberKey,
                "phone_no" => $clean,
                "message" => $caption,
            ];

            $chText = curl_init("https://api.watzap.id/v1/send_message");
            curl_setopt_array($chText, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
                CURLOPT_POSTFIELDS => json_encode($textData),
                CURLOPT_TIMEOUT => 12,
            ]);
            $textResponse = curl_exec($chText);
            $textErr = curl_error($chText);
            $httpText = curl_getinfo($chText, CURLINFO_HTTP_CODE);
            curl_close($chText);

            Log::info(
                "WA: send_message -> http={$httpText}, err={$textErr}, resp=" .
                    substr((string) $textResponse, 0, 1000),
            );

            // ----- kirim file via URL (send_file_url) -----
            $fileData = [
                "api_key" => $apiKey,
                "number_key" => $numberKey,
                "phone_no" => $clean,
                "url" => $pdfUrl,
                "filename" => $filename,
            ];

            $chFile = curl_init("https://api.watzap.id/v1/send_file_url");
            curl_setopt_array($chFile, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
                CURLOPT_POSTFIELDS => json_encode($fileData),
                CURLOPT_TIMEOUT => 25,
            ]);
            $fileResponse = curl_exec($chFile);
            $fileErr = curl_error($chFile);
            $httpFile = curl_getinfo($chFile, CURLINFO_HTTP_CODE);
            curl_close($chFile);

            Log::info(
                "WA: send_file_url -> http={$httpFile}, err={$fileErr}, resp=" .
                    substr((string) $fileResponse, 0, 1000),
            );

            // ----- evaluasi hasil -----
            $okText = false;
            $okFile = false;

            $textJson = json_decode((string) $textResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($textJson)) {
                if (
                    (isset($textJson["status"]) &&
                        in_array(strtolower($textJson["status"]), [
                            "ok",
                            "success",
                        ])) ||
                    !empty($textJson["success"])
                ) {
                    $okText = true;
                }
            } else {
                if ($httpText >= 200 && $httpText < 300 && empty($textErr)) {
                    $okText = true;
                }
            }

            $fileJson = json_decode((string) $fileResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($fileJson)) {
                if (
                    (isset($fileJson["status"]) &&
                        in_array(strtolower($fileJson["status"]), [
                            "ok",
                            "success",
                        ])) ||
                    !empty($fileJson["success"])
                ) {
                    $okFile = true;
                }
            } else {
                if ($httpFile >= 200 && $httpFile < 300 && empty($fileErr)) {
                    $okFile = true;
                }
            }

            $success = $okFile || $okText;

            $result = [
                "success" => $success,
                "phone_clean" => $clean,
                "pdf_url" => $pdfUrl,
                "file_exists" => $fileExists,
                "file_size" => $fileSize,
                "file_mtime" => $fileMTime,
                "http_text" => $httpText,
                "resp_text" => $textResponse,
                "err_text" => $textErr,
                "http_file" => $httpFile,
                "resp_file" => $fileResponse,
                "err_file" => $fileErr,
                "ok_text" => $okText,
                "ok_file" => $okFile,
            ];

            if ($success) {
                Log::info("WA: send overall success for {$clean}");
                return $result;
            }

            Log::warning("WA: send overall failed for {$clean}", $result);
            return $result;
        } catch (\Exception $e) {
            Log::error("WA: Exception - " . $e->getMessage());
            return ["success" => false, "msg" => $e->getMessage()];
        }
    }
}
