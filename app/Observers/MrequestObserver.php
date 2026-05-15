<?php

namespace App\Observers;

use App\Models\mrequest;
use App\Jobs\SendEmailOnChange;

class MrequestObserver
{
    /**
     * Handle the mrequest "created" event.
     */
    public function created(mrequest $model)
    {
        SendEmailOnChange::dispatch($model)->onQueue('hrd');
    }

    /**
     * Handle the mrequest "updated" event.
     */
    public function updated(mrequest $model): void
    {
        SendEmailOnChange::dispatch($model)->onQueue('hrd');
    }

    /**
     * Handle the mrequest "deleted" event.
     */
    public function deleted(mrequest $model): void
    {
        //
    }

    /**
     * Handle the mrequest "restored" event.
     */
    public function restored(mrequest $mrequest): void
    {
        //
    }

    /**
     * Handle the mrequest "force deleted" event.
     */
    public function forceDeleted(mrequest $mrequest): void
    {
        //
    }
}
