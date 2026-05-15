<?php

namespace App\Http\Controllers;

use App\Models\MagendaReminder;
use Illuminate\Support\Facades\DB;
use App\Models\Magenda;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Jobs\ScheduleAgendaReminderJob;

class MagendaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper → paksa datetime jadi WIB string (NO Z)
    |--------------------------------------------------------------------------
    */
    private function formatDates($items)
    {
        return $items->map(function ($item) {

            $item->start_at = optional($item->start_at)
                ->timezone('Asia/Jakarta')
                ->format('Y-m-d\TH:i:s');

            $item->end_at = optional($item->end_at)
                ->timezone('Asia/Jakarta')
                ->format('Y-m-d\TH:i:s');

            return $item;
        });
    }


    /*
    |--------------------------------------------------------------------------
    | LIST AGENDA
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $data = Magenda::with('reminders')
            ->active()
            ->between($request->start, $request->end)
            ->orderBy('start_at')
            ->get();

        return response()->json($this->formatDates($data));
    }


    /*
    |--------------------------------------------------------------------------
    | BY DATE
    |--------------------------------------------------------------------------
    */
    public function byDate($date)
    {
        $data = Magenda::with('reminders')
            ->active()
            ->date($date)
            ->orderBy('start_at')
            ->get();

        return response()->json($this->formatDates($data));
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        DB::beginTransaction();

        try {

            $user = $request->user();

            $agenda = Magenda::create([
                'title' => $request->title,
                'description' => $request->description,

                'start_at' => Carbon::parse(
                    $request->start_at,
                    'Asia/Jakarta'
                ),

                'end_at' => $request->end_at
                    ? Carbon::parse(
                        $request->end_at,
                        'Asia/Jakarta'
                    )
                    : null,

                'is_all_day' => $request->is_all_day ?? 0,
                'color' => $request->color ?? '#3B82F6',
                'status' => 'active',
                'user_id' => null,
                'dept_id' => $user->niddept,
                'created_by' => $user->nid,
            ]);

            /*
        |--------------------------------------------------------------------------
        | SAVE REMINDERS
        |--------------------------------------------------------------------------
        */

            if ($request->has('reminders')) {

                foreach ($request->reminders as $r) {

                    if (
                        empty($r['value']) ||
                        empty($r['unit'])
                    ) {
                        continue;
                    }

                    MagendaReminder::create([
                        'agenda_id' => $agenda->id,
                        'reminder_value' => $r['value'],
                        'reminder_unit' => $r['unit'],
                        'is_sent' => 0,
                    ]);
                }
            }

            DB::commit();

            /*
        |--------------------------------------------------------------------------
        | SCHEDULE REMINDERS KE QUEUE
        |--------------------------------------------------------------------------
        */

            ScheduleAgendaReminderJob::dispatch($agenda)->onQueue('hrd');

            return response()->json([
                'message' => 'Agenda berhasil ditambahkan',
                'data' => $agenda->load('reminders')
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan agenda',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $agenda = Magenda::findOrFail($id);

            $agenda->update([
                'title' => $request->title,
                'description' => $request->description,

                'start_at' => Carbon::parse(
                    $request->start_at,
                    'Asia/Jakarta'
                ),

                'end_at' => $request->end_at
                    ? Carbon::parse(
                        $request->end_at,
                        'Asia/Jakarta'
                    )
                    : null,

                'is_all_day' => $request->is_all_day ?? 0,
                'color' => $request->color,
            ]);

            /*
        |--------------------------------------------------------------------------
        | RESET REMINDERS
        |--------------------------------------------------------------------------
        */

            $agenda->reminders()->delete();

            /*
        |--------------------------------------------------------------------------
        | INSERT NEW REMINDERS
        |--------------------------------------------------------------------------
        */

            if ($request->has('reminders')) {

                foreach ($request->reminders as $r) {

                    if (
                        empty($r['value']) ||
                        empty($r['unit'])
                    ) {
                        continue;
                    }

                    MagendaReminder::create([
                        'agenda_id' => $agenda->id,
                        'reminder_value' => $r['value'],
                        'reminder_unit' => $r['unit'],
                        'is_sent' => 0,
                    ]);
                }
            }

            DB::commit();

            /*
        |--------------------------------------------------------------------------
        | RE-SCHEDULE REMINDERS KE QUEUE (timing mungkin berubah)
        |--------------------------------------------------------------------------
        */

            ScheduleAgendaReminderJob::dispatch($agenda)->onQueue('hrd');

            return response()->json([
                'message' => 'Agenda berhasil diupdate',
                'data' => $agenda->load('reminders')
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal update agenda',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $agenda = Magenda::findOrFail($id);

        // Hapus reminder terkait terlebih dahulu
        $agenda->reminders()->delete();

        // Hapus agenda dari database
        $agenda->delete();

        return response()->json([
            'message' => 'Agenda berhasil dihapus'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */
    public function mobile($month)
    {
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = Carbon::parse($month . '-01')->endOfMonth();

        $data = Magenda::with('reminders')
            ->active()
            ->whereBetween('start_at', [$start, $end])
            ->orderBy('start_at')
            ->get();

        return response()->json($this->formatDates($data));
    }
}
