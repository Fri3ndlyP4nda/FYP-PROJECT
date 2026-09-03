<?php

namespace App\Domain\Security;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Records authentication events into the existing audit trail.
 *
 * Nothing about signing in was written anywhere. Every deliberate action inside
 * the application was logged - assigning an evaluator, verifying a payment,
 * finalising a decision - but the front door was silent, so there was no way to
 * answer the questions that actually get asked after an incident: was this
 * account attacked, from where, how many times, and did anyone get in.
 *
 * Rate limiting already refuses a burst of attempts. Refusing them and never
 * recording them means the attempt leaves no trace at all, which is the
 * difference between a system that resisted an attack and a system where nobody
 * can tell whether one happened.
 *
 * The existing log() helpers in the admin controllers assume an authenticated
 * user and read Auth::user()->name directly. A failed sign-in has no user, so
 * this exists rather than making those helpers null-safe and inviting a caller
 * to log staff actions with no actor.
 */
class AuthLog
{
    public const SIGNED_IN = 'Signed In';

    public const SIGN_IN_FAILED = 'Sign-In Failed';

    public const SIGNED_OUT = 'Signed Out';

    public const REGISTERED = 'Account Registered';

    public const SECURITY_CHECK_FAILED = 'Security Check Failed';

    public const TWO_FACTOR_FAILED = 'Two-Factor Failed';

    public const RESET_REQUESTED = 'Password Reset Requested';

    public const RESET_COMPLETED = 'Password Reset Completed';

    /**
     * Record an event.
     *
     * $email is the address that was *attempted*, which is not necessarily an
     * account that exists - that is exactly what makes it worth recording.
     */
    public static function record(
        Request $request,
        string $action,
        ?string $email = null,
        ?User $user = null,
        ?string $note = null,
    ): void {
        $safeEmail = self::safeEmail($email);

        // Resolve the account when the caller did not, so the trail can answer
        // "which account was targeted" and not merely "someone failed".
        if ($user === null && $safeEmail !== null) {
            $user = User::where('email', strtolower($safeEmail))->first();
        }

        $description = match (true) {
            $note !== null => $note,
            $safeEmail !== null => $action.' for '.$safeEmail,
            default => $action,
        };

        try {
            ActivityLog::create([
                'user_id' => $user ? (string) $user->_id : null,
                'user_name' => $user->name ?? 'Unknown',
                'user_role' => $user->role ?? 'guest',
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // An audit write must never be the reason someone cannot sign in.
            // The event is lost; the login is not.
            report($e);
        }
    }

    /**
     * The submitted address, but only when it is actually an address.
     *
     * People type their password into the email field. Writing whatever arrived
     * straight into the audit trail would put cleartext passwords in a
     * collection that administrators can read, which is a worse leak than the
     * one the log exists to detect. Anything that is not a syntactically valid
     * address is recorded as malformed instead of verbatim.
     */
    private static function safeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        if ($email === '') {
            return null;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '(malformed address)';
        }

        return mb_strimwidth($email, 0, 190, '…');
    }
}
