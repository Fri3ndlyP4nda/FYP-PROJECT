<?php

namespace App\Domain\Security;

use Illuminate\Http\Request;

/**
 * Two cheap checks that sit alongside the proof of work.
 *
 * Neither proves anything on its own and neither should be relied on alone.
 * They cost a naive script nothing to defeat once it has been written *for this
 * site*, which is exactly the point: together with the proof of work they mean
 * a generic credential-stuffing tool does not work here out of the box, and
 * someone has to sit down and target this application specifically.
 *
 * Both are silent. A bot that learns why it was refused can adapt, so a failure
 * here is reported to the user as an ordinary validation error and nothing on
 * the page ever names the trap.
 */
class HumanSignals
{
    /** The hidden field's name. Deliberately plausible, so a form-filler tries it. */
    public const HONEYPOT = 'contact_reference';

    /** The timestamp field, carrying when the form was rendered. */
    public const RENDERED_AT = 'form_opened_at';

    /**
     * The floor, in seconds, between a form being rendered and submitted.
     *
     * A person has to read three labels, type an email, type a password and
     * wait for a proof of work that itself takes about half a second. Under two
     * seconds is not someone in a hurry; it is a script that filled the fields
     * the instant the page parsed.
     */
    private const MINIMUM_SECONDS = 2;

    /**
     * The ceiling. A form open for hours is usually an abandoned tab, and its
     * proof of work has expired anyway - this just gives a clearer failure.
     */
    private const MAXIMUM_SECONDS = 3600;

    /** Null when the submission looks human; otherwise a short reason for the log. */
    public static function check(Request $request): ?string
    {
        // Nothing visible asks for this, so anything in it came from something
        // that filled every field it found.
        if (filled($request->input(self::HONEYPOT))) {
            return 'honeypot';
        }

        $openedAt = (int) $request->input(self::RENDERED_AT, 0);

        if ($openedAt <= 0) {
            return 'no_timestamp';
        }

        $elapsed = time() - $openedAt;

        if ($elapsed < self::MINIMUM_SECONDS) {
            return 'too_fast';
        }

        if ($elapsed > self::MAXIMUM_SECONDS) {
            return 'too_slow';
        }

        return null;
    }
}
