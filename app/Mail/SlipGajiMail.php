<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SlipGajiMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $pdfData;
    public $pdfBinary;
    public $filename;

    public function __construct(array $pdfData, string $pdfBinary, string $filename)
    {
        $this->pdfData = $pdfData;
        $this->pdfBinary = $pdfBinary;
        $this->filename = $filename;
    }

    public function build()
    {
        return $this->subject('Slip Gaji ' . ($this->pdfData['bulan'] ?? ''))
                    ->view('emails.slip_gaji')
                    ->with(['data' => $this->pdfData]) // <- kirim sebagai $data supaya view yang lama bekerja
                    ->attachData($this->pdfBinary, $this->filename, [
                        'mime' => 'application/pdf',
                    ]);
    }
}
