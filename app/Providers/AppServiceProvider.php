<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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
        /**
         * Eloquent silently drops any key that is not in $fillable, with no
         * error. That is how advisor_name, advisor_approved_at and
         * mode_of_assessment were collected on a required form field, used in
         * the notification email, and then thrown away - leaving the printed
         * portfolio to attribute approval to whoever the view's ?? fallback
         * named. Outside production, make that failure loud instead of silent.
         */
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
