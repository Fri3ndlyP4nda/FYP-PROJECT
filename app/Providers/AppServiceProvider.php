<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->configureRateLimiters();
    }

    /**
     * Rate limiters for the credential-accepting endpoints.
     *
     * A plain throttle:5,1 keys on IP alone, which is wrong for this deployment:
     * a university campus sits behind NAT, so one student mistyping a password
     * five times would lock out everyone else sharing that address. Each limiter
     * below therefore uses a two-tier key - a tight limit on the individual
     * identity being attacked, and a looser per-IP ceiling that still stops
     * credential spraying without punishing a shared network.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by($this->identityKey($request)),
            Limit::perMinute(30)->by($request->ip()),
        ]);

        // No email is submitted at the 2FA step; the pending user id identifies it.
        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinutes(10, 5)->by((string) $request->session()->get('2fa_user_id', $request->ip())),
            Limit::perMinutes(10, 30)->by($request->ip()),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by($this->identityKey($request)),
            Limit::perMinutes(10, 15)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinutes(10, 5)->by($request->ip()),
        ]);
    }

    private function identityKey(Request $request): string
    {
        $email = strtolower(trim((string) $request->input('email')));

        return sha1($email . '|' . $request->ip());
    }
}
