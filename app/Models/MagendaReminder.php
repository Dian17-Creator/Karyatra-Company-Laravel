<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagendaReminder extends Model
{
    protected $table = 'magenda_reminder';

    protected $fillable = [
        'agenda_id',
        'reminder_value',
        'reminder_unit',
        'is_sent',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
    ];

    public function agenda()
    {
        return $this->belongsTo(Magenda::class, 'agenda_id');
    }
}
