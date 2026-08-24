<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if (!$user) {
            return back()->withErrors([
                'email' => 'No user found with that email address.',
            ])->withInput();
        }

        $plainToken = Str::random(64);

        PasswordResetToken::where('email', $email)->delete();

        PasswordResetToken::create([
            'email' => $email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $resetLink = url('/reset-password/' . $plainToken . '?email=' . urlencode($email));

        try {
            Mail::to($email)->send(new ResetPasswordMail($user, $resetLink));
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => $e->getMessage(),
            ])->withInput();
        }

        return back()->with('success', 'Password reset link has been sent to your email.');
    }

    public function showResetForm(Request $request, $token)
    {
        $email = $request->query('email');

        if (!$email) {
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

        if (!$record) {
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

        if (!Hash::check($request->token, $record->token)) {
            return back()->withErrors([
                'email' => 'Invalid reset token.',
            ])->withInput();
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'User account not found.',
            ])->withInput();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $record->delete();

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in.');
    }
}
