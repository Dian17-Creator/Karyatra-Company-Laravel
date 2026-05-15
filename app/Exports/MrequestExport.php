<?php

namespace App\Exports;

use App\Models\mrequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class MrequestExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $start;
    protected $end;

    public function __construct($userId = null, $start = null, $end = null)
    {
        $this->userId = $userId;
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        $query = mrequest::with('user');

        if ($this->userId) {
            $query->where(function ($q) {
                $q->where('nuserid', $this->userId)
                  ->orWhere('nuserId', $this->userId);
            });
        }

        if ($this->start && $this->end) {
            $query->whereBetween('drequest', [
                $this->start . ' 00:00:00',
                $this->end   . ' 23:59:59',
            ]);
        }

        return $query->orderBy('drequest', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tanggal',
            'Alasan',
            'Lokasi',
            'Status Approved',
            'Foto',
            // 'User',
        ];
    }

    public function map($item): array
    {
        // 🛡️ AMAN TANGGAL
        try {
            $tanggal = $item->drequest
                ? \Carbon\Carbon::parse($item->drequest)->format('d/m/Y')
                : '-';
        } catch (\Throwable $e) {
            $tanggal = '-';
        }

        // 🛡️ AMAN STRING
        $alasan = is_string($item->creason)
            ? trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $item->creason))
            : '-';

        $lokasi = '-';
        if (is_string($item->cplacename) && $item->cplacename !== '') {
            $lokasi = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $item->cplacename));
        } elseif (!empty($item->nlat) && !empty($item->nlng)) {
            $lokasi = $item->nlat . ', ' . $item->nlng;
        }

        // 🛡️ STATUS (SUDAH OK)
        $status = 'Pending';
        if ($item->fadmreq == 1) {
            $status = 'Approved by Admin';
        } elseif ($item->cstatus === 'rejected') {
            $status = 'Rejected by Captain';
        } elseif ($item->chrdstat === 'rejected') {
            $status = 'Rejected by HRD';
        } elseif ($item->cstatus === 'approved' && $item->chrdstat === 'approved') {
            $status = 'Approved by Captain & HRD';
        } elseif ($item->chrdstat === 'approved') {
            $status = 'Approved by HRD';
        } elseif ($item->cstatus === 'approved') {
            $status = 'Approved by Captain';
        }

        // 🛡️ FOTO
        //$foto = is_string($item->cphoto_path) && $item->cphoto_path !== ''
        //    ? 'https://absensi.matahati.my.id/' . ltrim($item->cphoto_path, '/')
        //    : '-';

        $foto = is_string($item->cphoto_path) && $item->cphoto_path !== ''
            ? url('/' . ltrim($item->cphoto_path, '/'))
            : '-';

        return [
            (string) $item->nid,
            $tanggal,
            $alasan,
            $lokasi,
            $status,
            $foto,
        ];
    }
}
