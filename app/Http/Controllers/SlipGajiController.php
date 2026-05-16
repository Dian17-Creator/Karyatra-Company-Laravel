<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\muser;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Mail\SlipGajiMail;
use Illuminate\Support\Str;

use function _PHPStan_781aefaf6\React\Async\delay;

class SlipGajiController extends Controller
{
    public function importAndSend(Request $request)
    {
        $request->validate([
            "file" => "required|file|mimes:xlsx,xls,csv|max:10240",
            "preview" => "nullable|in:1",
        ]);

        $uploadPath =
            rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/karyatrahrd/slipgaji/";

        // Pastikan folder ada
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        try {
            Log::info(
                "SlipGaji: importAndSend started by user_id=" .
                    optional(auth()->user())->nid,
            );

            $rows = Excel::toArray([], $request->file("file"))[0] ?? [];
            if (count($rows) <= 1) {
                return back()->with(
                    "slip_error",
                    "File kosong atau hanya header.",
                );
            }

            $rawHeader = $rows[0];
            $header = array_map(
                fn($h) => strtolower(
                    preg_replace("/\s+/", "_", trim((string) $h)),
                ),
                $rawHeader,
            );

            $headerMap = [];
            foreach ($header as $idx => $h) {
                if (in_array($h, ["nama", "name", "employee"])) {
                    $headerMap["nama"] = $idx;
                } elseif (in_array($h, ["email", "gmail", "alamat_email"])) {
                    $headerMap["email"] = $idx;
                } elseif (in_array($h, ["no_telp", "telepon", "hp", "no_hp"])) {
                    $headerMap["no_telp"] = $idx;
                } else {
                    $headerMap[$h] = $idx;
                }
            }

            $previewMode = $request->input("preview") == "1";
            $previewList = [];
            $sent = 0;
            $failed = 0;
            $skipped = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (
                    !is_array($row) ||
                    empty(array_filter(array_map("trim", $row)))
                ) {
                    continue;
                }

                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), null);
                } elseif (count($row) > count($header)) {
                    $row = array_slice($row, 0, count($header));
                }

                $data = [];
                foreach ($headerMap as $key => $colIndex) {
                    $data[$key] = isset($row[$colIndex])
                        ? trim((string) $row[$colIndex])
                        : null;
                }

                $emailInFile = trim((string) ($data["email"] ?? ""));
                $phoneInFile = trim((string) ($data["no_telp"] ?? ""));

                // Temukan user berdasarkan email atau no_telp
                $normalizedPhone = preg_replace("/[^0-9]/", "", $phoneInFile);
                if (
                    strpos($normalizedPhone, "0") !== 0 &&
                    strpos($normalizedPhone, "62") !== 0
                ) {
                    $normalizedPhone = "0" . $normalizedPhone;
                }

                $user = muser::where("cmailaddress", $emailInFile)
                    ->orWhere("cphone", $phoneInFile)
                    ->orWhere("cphone", $normalizedPhone)
                    ->orWhere("cphone", "62" . ltrim($normalizedPhone, "0"))
                    ->first();

                if (!$user) {
                    $previewList[] = [
                        "row" => $i + 1,
                        "nama" => $data["nama"] ?? "-",
                        "to" => $emailInFile ?: $phoneInFile,
                        "status" => "user_not_found",
                        "message" => "Email/No HP tidak ditemukan di database",
                    ];
                    $failed++;
                    continue;
                }

                $to = trim((string) $user->cmailaddress);
                $phone = trim((string) $user->cphone);

                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $previewList[] = [
                        "row" => $i + 1,
                        "nama" => $user->cname,
                        "to" => $to,
                        "status" => "invalid_email",
                        "message" => "Alamat email tidak valid",
                    ];
                    $skipped++;
                    continue;
                }

                if ($previewMode) {
                    $previewList[] = [
                        "row" => $i + 1,
                        "nama" => $user->cname,
                        "to" => $to,
                        "status" => "will_send",
                    ];
                    continue;
                }

                // 🔹 Data slip
                $pdfData = [
                    "email" => $emailInFile,
                    "bulan" => $data["bulan"] ?? now()->translatedFormat("F Y"),
                    "nama" => $user->cname,
                    "jabatan" => $data["jabatan"] ?? "-",
                    "hari_masuk" => (int) ($data["jumlah_hari"] ?? 0),
                    "gaji_pokok" => $this->toNumber($data["gaji_pokok"] ?? 0),
                    "tunjangan_makan" => $this->toNumber(
                        $data["tunjangan_makan"] ?? 0,
                    ),
                    "tunjangan_jabatan" => $this->toNumber(
                        $data["tunjangan_jabatan"] ?? 0,
                    ),
                    "tunjangan_transport" => $this->toNumber(
                        $data["tunjangan_transport"] ?? 0,
                    ),
                    "tunjangan_luar_kota" => $this->toNumber(
                        $data["tunjangan_luar_kota"] ?? 0,
                    ),
                    "tunjangan_masa_kerja" => $this->toNumber(
                        $data["tunjangan_masa_kerja"] ?? 0,
                    ),
                    "tunjangan_backup" => $this->toNumber(
                        $data["tunjangan_backup"] ?? 0,
                    ),
                    "lembur" => $this->toNumber($data["lembur"] ?? 0),
                    "tabungan_diambil" => $this->toNumber(
                        $data["tabungan_diambil"] ?? 0,
                    ),
                    "jumlah_penghasilan" => $this->toNumber(
                        $data["jumlah_penghasilan"] ?? 0,
                    ),
                    "potongan_keterlambatan" => $this->toNumber(
                        $data["potongan_keterlambatan"] ?? 0,
                    ),
                    "potongan_lain" => $this->toNumber(
                        $data["potongan_lain"] ?? 0,
                    ),
                    "potongan_tabungan" => $this->toNumber(
                        $data["potongan_tabungan"] ?? 0,
                    ),
                    "jumlah_potongan" => $this->toNumber(
                        $data["jumlah_potongan"] ?? 0,
                    ),
                    "gaji_diterima" => $this->toNumber(
                        $data["gaji_diterima"] ?? 0,
                    ),
                    "catatan" => $data["catatan"] ?? "",
                ];

                try {
                    $pdfBinary = Pdf::loadView("pdfs.slip_gaji", $pdfData)
                        ->setPaper("A4", "portrait")
                        ->output();
                } catch (\Exception $e) {
                    Log::error(
                        "SlipGaji: gagal generate PDF baris " .
                            ($i + 1) .
                            " - " .
                            $e->getMessage(),
                    );
                    $failed++;
                    continue;
                }

                $filename =
                    "SlipGaji_" .
                    Str::slug($user->cname) .
                    "_" .
                    date("Ym") .
                    ".pdf";
                $filePath = $uploadPath . $filename;

                try {
                    File::put($filePath, $pdfBinary);
                    Log::info("SlipGaji: tersimpan ke {$filePath}");
                } catch (\Exception $e) {
                    Log::error(
                        "SlipGaji: gagal simpan PDF - " . $e->getMessage(),
                    );
                    $failed++;
                    continue;
                }

                // 🔹 Kirim Email
                // try {
                //     Mail::to($to)->send(new SlipGajiMail($pdfData, $pdfBinary, $filename));
                //     Log::info("SlipGaji: email terkirim ke {$to}");
                //     $sent++;
                // } catch (\Exception $e) {
                //     Log::error("SlipGaji: gagal kirim email ke {$to} - " . $e->getMessage());
                //     $failed++;
                // }
                // 🔹 Kirim WhatsApp via Watzap API v1 (JSON POST)
                try {
                    if (!empty($phone)) {
                        $cleanPhone = preg_replace("/[^0-9]/", "", $phone);
                        if (strpos($cleanPhone, "0") === 0) {
                            $cleanPhone = "62" . substr($cleanPhone, 1);
                        }

                        //$pdfUrl = "https://absensi.matahati.my.id/uploads/slipgaji/{$filename}";
                        $pdfUrl = url("karyatrahrd/slipgaji/" . $filename);
                        $apiKey = env("WATZAP_API_KEY");
                        $numberKey = env("WATZAP_NUMBER_KEY");

                        $caption =
                            "*Slip Gaji Mata Hati Café*\n\n" .
                            "Halo *{$user->cname}*, slip gaji bulan *{$pdfData["bulan"]}* sudah tersedia.\n" .
                            "_Berikut slip gaji kamu._\n\n" .
                            "_Terima kasih_";

                        // --- 1️⃣ kirim teks dulu
                        $textData = [
                            "api_key" => $apiKey,
                            "number_key" => $numberKey,
                            "phone_no" => $cleanPhone,
                            "message" => $caption,
                        ];

                        $ch1 = curl_init(
                            "https://api.watzap.id/v1/send_message",
                        );
                        curl_setopt_array($ch1, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_HTTPHEADER => [
                                "Content-Type: application/json",
                            ],
                            CURLOPT_POSTFIELDS => json_encode($textData),
                            CURLOPT_TIMEOUT => 10,
                        ]);
                        $textResponse = curl_exec($ch1);
                        curl_close($ch1);

                        // --- 2️⃣ kirim file PDF
                        $fileData = [
                            "api_key" => $apiKey,
                            "number_key" => $numberKey,
                            "phone_no" => $cleanPhone,
                            "url" => $pdfUrl,
                            "filename" =>
                                "SlipGaji_{$user->cname}_" .
                                date("Ym") .
                                ".pdf",
                        ];

                        $ch2 = curl_init(
                            "https://api.watzap.id/v1/send_file_url",
                        );
                        curl_setopt_array($ch2, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_HTTPHEADER => [
                                "Content-Type: application/json",
                            ],
                            CURLOPT_POSTFIELDS => json_encode($fileData),
                            CURLOPT_TIMEOUT => 20,
                        ]);
                        $fileResponse = curl_exec($ch2);
                        $curlError = curl_error($ch2);
                        $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        curl_close($ch2);

                        Log::info(
                            "SlipGaji: kirim teks + file ke {$cleanPhone}, http_code={$httpCode}, response=" .
                                substr($fileResponse, 0, 200) .
                                ", error={$curlError}",
                        );
                    } else {
                        Log::warning(
                            "SlipGaji: nomor WA kosong untuk {$user->cname}",
                        );
                    }

                    //sleep(30);
                } catch (\Exception $e) {
                    Log::error(
                        "SlipGaji: gagal kirim WA ke {$phone} - " .
                            $e->getMessage(),
                    );
                }
            }

            if ($previewMode) {
                return back()->with("slip_preview", $previewList);
            }

            $summary = "✅ Slip gaji selesai. Terkirim: {$sent}, Gagal: {$failed}, Dilewati: {$skipped}";
            Log::info("SlipGaji: finished - " . $summary);

            return back()->with("slip_status", $summary);
        } catch (\Exception $e) {
            Log::error("SlipGaji: unexpected error - " . $e->getMessage());
            return back()->with(
                "slip_error",
                "❌ Kesalahan: " . $e->getMessage(),
            );
        }
    }

    private function toNumber($val)
    {
        if ($val === null) {
            return 0;
        }
        $s = preg_replace("/[^\d\.\,]/", "", (string) $val);
        if (substr_count($s, ".") > 0 && substr_count($s, ",") > 0) {
            $s = str_replace(".", "", $s);
            $s = str_replace(",", ".", $s);
        } elseif (substr_count($s, ",") > 0 && substr_count($s, ".") === 0) {
            $s = str_replace(",", ".", $s);
        }
        return is_numeric($s) ? (float) $s : 0;
    }
}
