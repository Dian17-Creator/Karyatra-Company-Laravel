<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Magenda extends Model
{
    protected $table = 'magenda';

    protected $fillable = [
        'title',
        'description',
        'start_at',
        'end_at',
        'is_all_day',
        'color',
        'status',
        'user_id',
        'dept_id',
        'created_by'
    ];

    protected $casts = [
        'start_at' => 'datetime:Y-m-d\TH:i:s',
        'end_at'   => 'datetime:Y-m-d\TH:i:s',
        'is_all_day' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // agenda milik user tertentu
    public function user()
    {
        return $this->belongsTo(muser::class, 'user_id', 'nid');
    }

    public function department()
    {
        return $this->belongsTo(mdepartment::class, 'dept_id', 'id');
    }

    // pembuat agenda
    public function creator()
    {
        return $this->belongsTo(muser::class, 'created_by', 'nid');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (biar query gampang)
    |--------------------------------------------------------------------------
    */

    // hanya aktif
    public function scopeActive(Builder $q)
    {
        return $q->where('status', 'active');
    }

    // filter tanggal tertentu
    public function scopeDate(Builder $q, $date)
    {
        // include agendas that start on the date OR span the date (start before and end after)
        return $q->where(function (Builder $q2) use ($date) {
            $q2->whereDate('start_at', $date)
                ->orWhere(function (Builder $q3) use ($date) {
                    $q3->whereDate('start_at', '<=', $date)
                        ->where(function (Builder $q4) use ($date) {
                            $q4->whereDate('end_at', '>=', $date)
                                ->orWhereNull('end_at');
                        });
                });
        });
    }

    // filter range tanggal
    public function scopeBetween(Builder $q, $start, $end)
    {
        // include any agenda that overlaps with the given range
        return $q->where(function (Builder $q2) use ($start, $end) {
            // starts inside range
            $q2->whereBetween('start_at', [$start, $end])
                // or ends inside range
                ->orWhereBetween('end_at', [$start, $end])
                // or starts before range and ends after range
                ->orWhere(function (Builder $q3) use ($start, $end) {
                    $q3->where('start_at', '<=', $start)
                        ->where(function (Builder $q4) use ($end) {
                            $q4->where('end_at', '>=', $end)
                                ->orWhereNull('end_at');
                        });
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    // durasi menit
    public function getDurationMinutesAttribute()
    {
        if (!$this->end_at) {
            return 0;
        }

        return $this->start_at->diffInMinutes($this->end_at);
    }

    public function reminders()
    {
        return $this->hasMany(MagendaReminder::class, 'agenda_id');
    }
}
