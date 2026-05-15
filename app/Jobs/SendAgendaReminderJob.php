<?php

namespace App\Jobs;

use App\Models\MagendaReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendAgendaReminderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Coba ulang maksimal 3x jika gagal */
    public int $tries = 3;

    /** Jeda antar retry (detik) */
    public int $backoff = 60;

    public $reminder;

    public function __construct(MagendaReminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Unique key per reminder agar tidak double-send
     * meskipun di-dispatch lebih dari sekali.
     */
    public function uniqueId(): int
    {
        return $this->reminder->id;
    }

    public function handle(): void
    {
        $reminder = $this->reminder;

        /*
        |------------------------------------------------------------------
        | AVOID DOUBLE SEND
        |------------------------------------------------------------------
        */

        if ($reminder->is_sent) {
            return;
        }

        $agenda = $reminder->agenda;

        if (!$agenda) {
            return;
        }

        $unitLabels = [
            'minute' => 'menit',
            'hour'   => 'jam',
            'day'    => 'hari',
            'week'   => 'minggu',
        ];

        $unitLabel = $unitLabels[$reminder->reminder_unit] ?? $reminder->reminder_unit;

        $response = Http::timeout(10)
            ->post(
                config('app.url') . '/api/send-notif',
                [
                    //'user_id' => $agenda->created_by,
                    'user_id' => 32,

                    'title' => 'Pengingat Agenda',

                    'body' =>
                    $agenda->title .
                        ' dimulai ' .
                        $reminder->reminder_value . ' ' .
                        $unitLabel .
                        ' lagi',

                    'type' => 'agenda',
                    'agenda_id' => $agenda->id
                ]
            );

        if ($response->successful()) {

            $reminder->update([
                'is_sent' => 1
            ]);
        }
    }
}
