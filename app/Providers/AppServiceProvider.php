<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
// use App\Models\mrequest;
use App\Models\MscanManual;
use App\Models\Tusercontract;
use App\Observers\TusercontractObserver;
// use App\Observers\MrequestObserver;
use App\Observers\MscanManualObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Tusercontract::observe(TusercontractObserver::class);
        Carbon::setLocale('id');
        //mrequest::observe(MrequestObserver::class);
        MscanManual::observe(MscanManualObserver::class);
    }
}
