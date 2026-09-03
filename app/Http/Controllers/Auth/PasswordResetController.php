<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Security\AuthLog;
use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLink(Request $request)
    {
        $this->normalizeEmail($request);

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        /*
         | Recorded whether or not the address is registered. The *response*
         | must stay identical either way - that is what stops this endpoint
         | being a membership oracle - but the log is internal, and an attacker
         | sweeping addresses is precisely what it should be able to show.
         */
        AuthLog::record($request, AuthLog::RESET_REQUESTED, $request->email, $user);

        /**
         * The response is deliberately identical whether or not the address is
         * registered. Returning "No user found with that email address." turned
         * this endpoint into a membership oracle for the entire user base, which
         * an attacker can use to enumerate staff and student accounts before
         * targeting them. The login form is already generic; this matches it.
         */
        if ($user) {
            $plainToken = Str::random(64);

            PasswordResetToken::where('email', $email)->delete();

            PasswordResetToken::create([
                'email' => $email,
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]);

            $resetLink = url('/reset-password/'.$plainToken.'?email='.urlencode($email));

            try {
                Mail::to($email)->send(new ResetPasswordMail($user, $resetLink));
            } catch (\Exception $e) {
                /**
                 * Never surface the transport exception. Symfony Mailer embeds the
                 * SMTP host, port, auth mechanism and raw server dialogue in these
                 * messages, and this endpoint is unauthenticated. Every other mail
                 * call in the application already logs and swallows; this one was
                 * the sole leak.
                 */
                Log::error('Password reset mail error: '.$e->getMessage());
            }
        }

        return back()->with(
            'success',
            'If that email address has an account, a password reset link has been sent to it.'
        );
    }

    public function showResetForm(Request $request, $token)
    {
        $email = $request->query('email');

        if (! $email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Invalid reset link.']);
        }

        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $this->normalizeEmail($request);

        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
        ]);

        $email = strtolower($request->email);

        $record = PasswordResetToken::where('email', $email)->first();

        if (! $record) {
            return back()->withErrors([
                'email' => 'Invalid or expired password reset request.',
            ])->withInput();
        }

        $isExpired = Carbon::parse($record->created_at)->addMinutes(60)->isPast();

        if ($isExpired) {
            $record->delete();

            return back()->withErrors([
                'email' => 'This reset link has expired.',
            ])->withInput();
        }

        if (! Hash::check($request->token, $record->token)) {
            return back()->withErrors([
                'email' => 'Invalid reset token.',
            ])->withInput();
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'User account not found.',
            ])->withInput();
        }

        $user->password = Hash::make($request->password);

        /**
         * Resetting a password is how someone recovers a compromised account, so
         * it has to actually evict the intruder. Cycling remember_token
         * invalidates any "remember me" cookie, and clearing last_2fa_verified_at
         * closes the 30-minute window during which a second login skips the OTP
         * entirely - without this, an attacker holding a live session simply kept
         * it, and kept the 2FA bypass with it.
         */
        $user->remember_token = Str::random(60);
        $user->last_2fa_verified_at = null;
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        $record->delete();

        AuthLog::record($request, AuthLog::RESET_COMPLETED, $user->email, $user);

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in.');
    }
}
