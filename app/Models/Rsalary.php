<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Rsalary extends Model
{
    use HasFactory;

    protected $table = 'Rsalary';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'period_year',
        'period_month',
        'jabatan',
        'jumlah_masuk',
        'jenis_gaji',
        'contract_nominal',
        'gaji_harian',
        'gaji_pokok',
        'tunjangan_makan',
        'tunjangan_jabatan',
        'tunjangan_transport',
        'tunjangan_luar_kota',
        'tunjangan_masa_kerja',
        'tunjangan_backup',
        'gaji_lembur',
        'bonus_kehadiran',
        'tabungan_diambil',
        'potongan_lain',
        'potongan_tabungan',
        'potongan_keterlambatan',
        'total_gaji',

        'pdf_url',
        'email_status',
        'email_sent_at',

        'status',
        'user_updated_at',
        'user_note',

        'note',
        'keterangan_absensi',
        'reasonedit',
    ];
    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'jumlah_masuk' => 'integer',
        'jenis_gaji' => 'string',
        'contract_nominal' => 'decimal:2',
        'gaji_harian' => 'decimal:2',
        'gaji_pokok' => 'decimal:2',
        'tunjangan_makan' => 'decimal:2',
        'tunjangan_jabatan' => 'decimal:2',
        'tunjangan_transport' => 'decimal:2',
        'tunjangan_luar_kota' => 'decimal:2',
        'tunjangan_masa_kerja' => 'decimal:2',
        'tunjangan_backup' => 'decimal:2',
        'gaji_lembur' => 'decimal:2',
        'bonus_kehadiran' => 'decimal:2',
        'tabungan_diambil' => 'decimal:2',
        'potongan_lain' => 'decimal:2',
        'potongan_tabungan' => 'decimal:2',
        'potongan_keterlambatan' => 'decimal:2',
        'total_gaji' => 'decimal:2',
        'status' => 'string',
        'email_status' => 'string',
        'email_sent_at' => 'datetime',
        'user_updated_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(\App\Models\muser::class, 'user_id', 'nid');
    }
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }
    public function getTotalTunjanganAttribute()
    {
        return (float) (
            ($this->tunjangan_makan ?? 0)
            + ($this->tunjangan_jabatan ?? 0)
            + ($this->tunjangan_transport ?? 0)
            + ($this->tunjangan_luar_kota ?? 0)
            + ($this->tunjangan_masa_kerja ?? 0)
            + ($this->tunjangan_backup ?? 0)
        );
    }
    public function getTotalPotonganAttribute()
    {
        return (float) (
            ($this->potongan_lain ?? 0)
            + ($this->potongan_tabungan ?? 0)
            + ($this->tabungan_diambil ?? 0)
            + ($this->potongan_keterlambatan ?? 0)
        );
    }
    public function getComputedGajiPokokAttribute()
    {
        $harian = (float) ($this->gaji_harian ?? 0);
        $masuk = (int) ($this->jumlah_masuk ?? 0);

        return round($harian * $masuk, 2);
    }
    public function getGajiKotorAttribute()
    {
        $pokok = (float) ($this->gaji_pokok ?? $this->computed_gaji_pokok);

        return round(
            $pokok +
            $this->total_tunjangan +
            ($this->gaji_lembur ?? 0) +
            ($this->bonus_kehadiran ?? 0), // 🔥 TAMBAHAN
            2
        );
    }
    public function getGajiBersihAttribute()
    {
        return round($this->gaji_kotor - $this->total_potongan, 2);
    }
    public function formatRupiah($field, $prefix = 'Rp ')
    {
        $val = $this->{$field} ?? 0;
        return $prefix . number_format($val, 0, ',', '.');
    }
}
