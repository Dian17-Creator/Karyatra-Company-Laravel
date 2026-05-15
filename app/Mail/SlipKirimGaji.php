<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SlipKirimGaji extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $row;
    public $filePath;

    public function __construct($row, $filePath)
    {
        $this->row = $row;
        $this->filePath = $filePath;
    }

    public function build()
    {
        $nama = $this->row->user->cfullname ?? $this->row->user->cname ?? '-';

        $bulanFormat = \Carbon\Carbon::create(
            $this->row->period_year,
            $this->row->period_month,
            1
        )
        ->locale('id')                // 🔥 bahasa indonesia
        ->translatedFormat('F Y');    // 🔥 "Februari 2026"

        return $this->subject("Slip Gaji {$bulanFormat} - {$nama}")
            ->view('emails.slip_gaji')
            ->with([
                'data' => [
                    'nama' => $nama,
                    'bulan' => $bulanFormat,
                    'tanggal_cetak' => now()->format('d/m/Y'),
                ]
            ])
            ->attach($this->filePath);
    }

}
