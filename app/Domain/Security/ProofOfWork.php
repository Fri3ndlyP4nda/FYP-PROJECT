<?php

namespace App\Domain\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A computational challenge in place of the arithmetic captcha.
 *
 * The captcha this replaces printed "3 + 5 = ?" into the page and compared the
 * answer against the session. Any script solved it with one regular expression
 * and an addition, and even a script that did not bother parsing had a 1-in-17
 * chance of guessing, because the sum of two digits between 1 and 9 has only
 * seventeen possible values.
 *
 * The point of a control here is not to prove a human is present - nothing on a
 * login form can do that. It is to make automation *cost* something, so that
 * trying thousands of stolen passwords is expensive rather than free. This
 * makes the browser search for a number:
 *
 *     find n where sha256(salt . n) === target,  0 <= n <= difficulty
 *
 * The client averages difficulty/2 hashes to find it - roughly half a second on
 * a laptop, and unavoidable, because there is no shortcut through a hash. The
 * server does exactly one hash to check. That asymmetry is the whole mechanism:
 * one login costs a person nothing they notice, and a hundred thousand costs an
 * attacker real CPU time.
 *
 * It is not unbreakable and should never be described as such. A determined
 * attacker pays the CPU, and a commercial solver farm would not notice it. What
 * it removes is the free, trivial, one-line bypass that exists today.
 *
 * Three properties matter as much as the work itself:
 *
 *   - The challenge is HMAC-signed with the application key, so a client cannot
 *     invent a challenge with difficulty 1 and solve that instead.
 *   - It is single-use. A solved challenge is burned on redemption, so one
 *     solution cannot be replayed across a thousand submissions - which would
 *     otherwise reduce the cost of the whole attack to one puzzle.
 *   - It expires, so a bank of pre-solved challenges cannot be built up in
 *     advance and spent later.
 */
class ProofOfWork
{
    /**
     * Search space. The browser tries about half of these before it finds the
     * answer; 150k lands around half a second in a modern engine and is
     * imperceptible next to the time it takes to type a password.
     */
    private const DIFFICULTY = 150000;

    /** How long a challenge stays solvable. */
    private const TTL_SECONDS = 900;

    private const CACHE_PREFIX = 'pow:';

    /**
     * A fresh challenge for one form render.
     *
     * @return array{salt:string,target:string,difficulty:int,expires:int,signature:string}
     */
    public static function issue(): array
    {
        $salt = Str::random(24);
        $answer = random_int(0, self::DIFFICULTY);
        $expires = time() + self::TTL_SECONDS;

        $challenge = [
            'salt' => $salt,
            'target' => hash('sha256', $salt.$answer),
            'difficulty' => self::DIFFICULTY,
            'expires' => $expires,
        ];

        // Signed last, over the finished challenge, so none of the fields above
        // - the difficulty in particular - can be edited in flight.
        $challenge['signature'] = self::sign($challenge);

        return $challenge;
    }

    /**
     * Check a submitted solution.
     *
     * Returns a reason string on failure so the caller can decide what to tell
     * the user; the caller should not repeat these verbatim, since "expired"
     * and "already used" are useful to an attacker and identical to a person.
     */
    public static function verify(array $solution): ?string
    {
        foreach (['salt', 'target', 'difficulty', 'expires', 'signature', 'answer'] as $field) {
            if (! isset($solution[$field]) || $solution[$field] === '') {
                return 'incomplete';
            }
        }

        $challenge = [
            'salt' => (string) $solution['salt'],
            'target' => (string) $solution['target'],
            'difficulty' => (int) $solution['difficulty'],
            'expires' => (int) $solution['expires'],
        ];

        // Signature before anything else: an unsigned challenge is not ours and
        // nothing else about it is worth reading.
        if (! hash_equals(self::sign($challenge), (string) $solution['signature'])) {
            return 'forged';
        }

        if ($challenge['expires'] < time()) {
            return 'expired';
        }

        // A challenge whose difficulty was lowered would fail the signature
        // check above, but assert the floor anyway so a future change to issue()
        // cannot quietly weaken every challenge already in flight.
        if ($challenge['difficulty'] < self::DIFFICULTY) {
            return 'forged';
        }

        $answer = (int) $solution['answer'];

        if ($answer < 0 || $answer > $challenge['difficulty']) {
            return 'out_of_range';
        }

        if (! hash_equals($challenge['target'], hash('sha256', $challenge['salt'].$answer))) {
            return 'wrong';
        }

        // Burn it. Without this, one solved challenge could be posted a
        // thousand times and the work would have been paid once.
        $key = self::CACHE_PREFIX.hash('sha256', $challenge['salt']);

        if (! Cache::add($key, true, self::TTL_SECONDS + 60)) {
            return 'replayed';
        }

        return null;
    }

    /** Signed with the application key, so a challenge cannot be minted elsewhere. */
    private static function sign(array $challenge): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [
                $challenge['salt'],
                $challenge['target'],
                $challenge['difficulty'],
                $challenge['expires'],
            ]),
            (string) config('app.key'),
        );
    }

    /**
     * Solve a challenge server-side.
     *
     * Only for tests, which must be able to post a valid solution without
     * running a browser. It is the same loop the client runs.
     */
    public static function solve(array $challenge): int
    {
        for ($n = 0; $n <= $challenge['difficulty']; $n++) {
            if (hash_equals($challenge['target'], hash('sha256', $challenge['salt'].$n))) {
                return $n;
            }
        }

        throw new \RuntimeException('Unsolvable challenge.');
    }
}
