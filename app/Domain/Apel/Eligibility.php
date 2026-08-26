<?php

namespace App\Domain\Apel;

/**
 * The entry rules for APEL A, in one place.
 *
 * The rule this replaces was `stripos($qualification, 'diploma') === 0` — the
 * candidate's highest qualification had to *begin with the word "Diploma"*.
 * A candidate holding a Bachelor's degree, a Master's, or an Advanced Diploma
 * was turned away for being under-qualified, and the same test was duplicated
 * in the controller and again in the scorecard, so the two could disagree.
 *
 * The intent is clearly "at least a Diploma". That is what this expresses.
 */
class Eligibility
{
    /**
     * Recognised qualification levels, lowest first. Anything at or above the
     * configured floor is accepted.
     */
    private const LEVELS = [
        'none' => 0,
        'certificate' => 1,
        'diploma' => 2,
        'advanced diploma' => 3,
        'bachelor' => 4,
        'degree' => 4,
        'master' => 5,
        'phd' => 6,
        'doctorate' => 6,
    ];

    /** Free-text qualification to a comparable level, or null if unrecognised. */
    public static function level(?string $qualification): ?int
    {
        $text = strtolower(trim((string) $qualification));

        if ($text === '') {
            return null;
        }

        // Longest key first so "advanced diploma" is not matched as "diploma".
        $keys = array_keys(self::LEVELS);
        usort($keys, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($keys as $key) {
            if ($key !== 'none' && str_contains($text, $key)) {
                return self::LEVELS[$key];
            }
        }

        return null;
    }

    public static function minimumLevel(): int
    {
        $floor = strtolower((string) config('apel.eligibility.minimum_qualification', 'diploma'));

        return self::LEVELS[$floor] ?? self::LEVELS['diploma'];
    }

    public static function minimumAge(): int
    {
        return (int) config('apel.eligibility.minimum_age', 30);
    }

    /** Does this qualification meet the floor for APEL A? */
    public static function qualificationAccepted(?string $qualification): bool
    {
        $level = self::level($qualification);

        return $level !== null && $level >= self::minimumLevel();
    }

    /** The sentence shown when it does not. */
    public static function qualificationMessage(?string $qualification): string
    {
        $floor = ucfirst((string) config('apel.eligibility.minimum_qualification', 'diploma'));

        if (self::level($qualification) === null) {
            return "We could not recognise \"{$qualification}\" as a qualification level. "
                . "Please state it in a form such as \"Diploma in Computer Science\" or \"Bachelor of Engineering\".";
        }

        return "APEL A requires a {$floor} or higher as the highest academic qualification.";
    }
}
