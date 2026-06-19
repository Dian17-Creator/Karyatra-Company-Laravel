<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MasterSchedule extends Model
{
    protected $table = 'mschedule';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'cname',
        'ctype',
        'ctotal',
        'dstart',
        'dend',
        'dstart2',
        'dend2',
        'dcreated',
        'ccompany',
    ];

    protected $casts = [
        'ctotal' => 'integer',
    ];

    public function userSchedules()
    {
        return $this->hasMany(UserSchedule::class, 'nidsched');
    }

    public function isFlexi(): bool
    {
        return $this->ctype === 'flexi';
    }

    public function isNormal(): bool
    {
        return $this->ctype === 'normal';
    }

    public function totalJam(): int
    {
        // fallback kalau ctotal null
        return $this->ctotal ?? 9;
    }

    public function calculatedTotalJam(): int
    {
        // FLEXI → pakai input manual
        if ($this->ctype === 'flexi') {
            return (int) ($this->ctotal ?? 0);
        }

        $totalMinutes = 0;

        // SESSION 1
        if ($this->dstart && $this->dend) {
            $start = Carbon::createFromFormat('H:i:s', $this->dstart);
            $end   = Carbon::createFromFormat('H:i:s', $this->dend);

            if ($end->greaterThan($start)) {
                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        // SESSION 2 (SPLIT)
        if ($this->dstart2 && $this->dend2) {
            $start2 = Carbon::createFromFormat('H:i:s', $this->dstart2);
            $end2   = Carbon::createFromFormat('H:i:s', $this->dend2);

            if ($end2->greaterThan($start2)) {
                $totalMinutes += $start2->diffInMinutes($end2);
            }
        }

        return (int) floor($totalMinutes / 60);
    }
}
