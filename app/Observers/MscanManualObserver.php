<?php

namespace App\Observers;

use App\Models\MscanManual;
use App\Jobs\SendEmailOnChange;

class MscanManualObserver
{
    /**
     * Handle the MscanManual "created" event.
     */
    public function created(MscanManual $model): void
    {
        SendEmailOnChange::dispatch($model)->onQueue('hrd');
    }

    /**
     * Handle the MscanManual "updated" event.
     */
    public function updated(MscanManual $model): void
    {
        SendEmailOnChange::dispatch($model)->onQueue('hrd');
    }

    /**
     * Handle the MscanManual "deleted" event.
     */
    public function deleted(MscanManual $model): void
    {
        //
    }

    /**
     * Handle the MscanManual "restored" event.
     */
    public function restored(MscanManual $model): void
    {
        //
    }

    /**
     * Handle the MscanManual "force deleted" event.
     */
    public function forceDeleted(MscanManual $model): void
    {
        //
    }
}
