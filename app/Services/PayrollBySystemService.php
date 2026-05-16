<?php

namespace App\Services;

use App\Models\Csalary;
use Illuminate\Support\Facades\Mail;
use App\Mail\SlipGajiMail;
use Illuminate\Support\Facades\File;

class PayrollBySystemService
{
    public function run()
    {
        $month = now()->subMonth()->month;
        $year = now()->subMonth()->year;

        $salaries = Csalary::where("status", "PENDING")
            // ->where('user_id', 32) // TEST ONLY
            ->where(function ($q) {
                $q->where("email_status", "!=", "SENT")->orWhereNull(
                    "email_status",
                );
            })
            ->where("period_month", $month)
            ->where("period_year", $year)
            ->get();

        foreach ($salaries as $salary) {
            $email = $salary->user->cmailaddress ?? null;

            if (!$salary->user || !$email) {
                continue;
            }

            if ($salary->status !== "PENDING") {
                continue;
            }

            try {
                $pdfPath = public_path(
                    "karyatrahrd/slipgaji/" . basename($salary->pdf_url),
                );

                if (!File::exists($pdfPath)) {
                    continue;
                }

                $pdfBinary = File::get($pdfPath);
                $filename = basename($pdfPath);

                $pdfData = $salary->toSlipPdfData();

                $salary->status = "BY_SYSTEM";

                Mail::to($email)->send(
                    new SlipGajiMail($pdfData, $pdfBinary, $filename),
                );

                // delay sebentar
                sleep(2);

                $salary->email_status = "SENT";
                $salary->email_sent_at = now();
            } catch (\Exception $e) {
                $salary->email_status = "FAILED";
            }

            $salary->save();
        }
    }
}
