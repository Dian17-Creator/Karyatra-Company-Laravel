<?php

namespace App\Jobs;

use App\Models\Magenda;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduleAgendaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Magenda $agenda;

    public function __construct(Magenda $agenda)
    {
        $this->agenda = $agenda;
    }

    public function handle(): void
    {
        $agenda = $this->agenda->fresh(['reminders']);

        if (!$agenda) {
            return;
        }

        $now = Carbon::now('Asia/Jakarta');

        $agendaTime = Carbon::parse($agenda->start_at, 'Asia/Jakarta');

        foreach ($agenda->reminders as $reminder) {

            /*
            |------------------------------------------------------------------
            | Hitung kapan reminder harus dikirim
            |------------------------------------------------------------------
            */

            $reminderAt = $agendaTime->copy();

            switch ($reminder->reminder_unit) {
                case 'minute':
                    $reminderAt->subMinutes($reminder->reminder_value);
                    break;
                case 'hour':
                    $reminderAt->subHours($reminder->reminder_value);
                    break;
                case 'day':
                    $reminderAt->subDays($reminder->reminder_value);
                    break;
                case 'week':
                    $reminderAt->subWeeks($reminder->reminder_value);
                    break;
                default:
                    continue 2;
            }

            /*
            |------------------------------------------------------------------
            | Skip jika waktu reminder sudah lewat
            |------------------------------------------------------------------
            */

            if ($reminderAt->lte($now)) {
                continue;
            }

            /*
            |------------------------------------------------------------------
            | Dispatch SendAgendaReminderJob dengan delay tepat waktu
            |------------------------------------------------------------------
            */

            $delay = $now->diffInSeconds($reminderAt);

            SendAgendaReminderJob::dispatch($reminder)
                ->onQueue('hrd')
                ->delay(now()->addSeconds($delay));
        }
    }
}
