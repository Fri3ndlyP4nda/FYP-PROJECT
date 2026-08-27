<?php

namespace Tests\Unit;

use App\Domain\Apel\Eligibility;
use App\Models\Application;
use App\Services\ApelDecisionSupportService;
use Tests\TestCase;

/**
 * The defect these tests exist to prevent:
 *
 * The entry rule used to be `stripos($qualification, 'diploma') === 0` — the
 * candidate's highest qualification had to *begin with the word "Diploma"*.
 * A candidate holding an Advanced Diploma, a Bachelor's, a Master's or a PhD
 * was therefore turned away as UNDER-qualified, and the identical rule was
 * written out twice (Student\ApplicationController and the APEL A scorecard),
 * so the two could disagree about the same candidate.
 *
 * The intent is "at least a Diploma". App\Domain\Apel\Eligibility expresses
 * that as a ranked ladder of levels with a configurable floor, read from
 * config('apel.eligibility.minimum_qualification') and
 * config('apel.eligibility.minimum_age').
 *
 * No database is touched here — this is the pure entry rule.
 */
class EligibilityTest extends TestCase
{
    /* ---------------------------------------------------------------- *
     | Everything at or above the floor is accepted
     * ---------------------------------------------------------------- */

    public function test_every_qualification_level_at_or_above_the_floor_is_accepted(): void
    {
        $atOrAboveTheFloor = [
            'Diploma in Business Studies',
            'Advanced Diploma in Mechanical Engineering',
            'Bachelor of Science',
            'Degree in Accounting',
            'Master of Engineering',
            'PhD in Computing',
            'Doctorate of Philosophy in Education',
        ];

        foreach ($atOrAboveTheFloor as $qualification) {
            $this->assertTrue(
                Eligibility::qualificationAccepted($qualification),
                "\"{$qualification}\" is at or above a Diploma and must be accepted for APEL A.",
            );
        }
    }

    /**
     * The four cases the old `stripos(..., 'diploma') === 0` rule got wrong.
     * Each of these is a candidate who is MORE qualified than the floor and
     * was being rejected for being under-qualified.
     */
    public function test_the_qualifications_the_old_begins_with_diploma_rule_rejected_are_all_accepted(): void
    {
        foreach (['Advanced Diploma', 'Bachelor of Science', 'Master of Engineering', 'PhD in Computing'] as $qualification) {
            $this->assertTrue(
                Eligibility::qualificationAccepted($qualification),
                "\"{$qualification}\" exceeds the APEL A entry floor and must not be rejected as under-qualified.",
            );
        }
    }

    public function test_a_qualification_that_merely_mentions_a_diploma_late_in_the_sentence_is_still_accepted(): void
    {
        $this->assertTrue(
            Eligibility::qualificationAccepted('Politeknik Malaysia Diploma'),
            'The floor is a level, not a prefix — the word need not start the sentence.',
        );
    }

    public function test_the_ladder_ranks_each_level_above_the_one_below_it(): void
    {
        $certificate = Eligibility::level('Certificate in Welding');
        $diploma = Eligibility::level('Diploma in Business');
        $advanced = Eligibility::level('Advanced Diploma in Business');
        $bachelor = Eligibility::level('Bachelor of Science');
        $master = Eligibility::level('Master of Engineering');
        $phd = Eligibility::level('PhD in Computing');

        $this->assertLessThan($diploma, $certificate);
        $this->assertLessThan($advanced, $diploma);
        $this->assertLessThan($bachelor, $advanced);
        $this->assertLessThan($master, $bachelor);
        $this->assertLessThan($phd, $master);
    }

    /**
     * "advanced diploma" contains "diploma", so a naive substring scan reports
     * an Advanced Diploma as a plain Diploma. It must rank above one.
     */
    public function test_an_advanced_diploma_outranks_a_plain_diploma(): void
    {
        $this->assertGreaterThan(
            Eligibility::level('Diploma in Business'),
            Eligibility::level('Advanced Diploma in Business'),
            'An Advanced Diploma must not be matched as an ordinary Diploma.',
        );
    }

    /**
     * Regression guard for a defect this suite found.
     *
     * level() used to scan the recognised keys longest-string-first and return
     * the FIRST match rather than the HIGHEST level named. "certificate" is a
     * longer string than "bachelor", so a candidate listing both was scored as
     * a certificate holder and rejected as under-qualified - the same false
     * under-qualification this class exists to prevent.
     *
     * It now keeps the maximum matching level, which preserves the
     * "advanced diploma" case (3 beats 2) without depending on key length.
     */
    public function test_a_qualification_naming_two_levels_is_read_at_the_higher_of_them(): void
    {
        $bachelor = Eligibility::level('Bachelor of Science');

        $this->assertSame($bachelor, Eligibility::level('Bachelor of Science with a Certificate in Teaching'));
        $this->assertSame($bachelor, Eligibility::level('Certificate in Teaching, Bachelor of Science'));
        $this->assertTrue(Eligibility::qualificationAccepted('Bachelor of Science with a Certificate in Teaching'));
    }

    public function test_a_degree_and_a_bachelor_are_treated_as_the_same_level(): void
    {
        $this->assertSame(
            Eligibility::level('Bachelor of Science'),
            Eligibility::level('Degree in Science'),
        );
    }

    /* ---------------------------------------------------------------- *
     | Everything below the floor is rejected
     * ---------------------------------------------------------------- */

    public function test_a_qualification_below_the_floor_is_rejected(): void
    {
        $belowTheFloor = [
            'Certificate in Welding',
            'Sijil Kemahiran Malaysia Certificate',
            'certificate',
        ];

        foreach ($belowTheFloor as $qualification) {
            $this->assertFalse(
                Eligibility::qualificationAccepted($qualification),
                "\"{$qualification}\" is below a Diploma and must not pass the APEL A entry floor.",
            );
        }
    }

    public function test_school_level_qualifications_do_not_pass_the_entry_floor(): void
    {
        foreach (['SPM', 'STPM', 'SPM 2009', 'UEC'] as $qualification) {
            $this->assertFalse(
                Eligibility::qualificationAccepted($qualification),
                "\"{$qualification}\" is a school qualification and must not pass the APEL A entry floor.",
            );
        }
    }

    public function test_stating_no_qualification_at_all_is_rejected(): void
    {
        $this->assertFalse(Eligibility::qualificationAccepted('None'));
        $this->assertFalse(Eligibility::qualificationAccepted('none'));
    }

    /* ---------------------------------------------------------------- *
     | Formatting: case and whitespace
     * ---------------------------------------------------------------- */

    public function test_the_qualification_check_ignores_case(): void
    {
        foreach (['DIPLOMA IN BUSINESS', 'diploma in business', 'DiPlOmA iN bUsInEsS'] as $qualification) {
            $this->assertTrue(
                Eligibility::qualificationAccepted($qualification),
                "\"{$qualification}\" should be read the same whatever the casing.",
            );
        }

        $this->assertSame(
            Eligibility::level('bachelor of science'),
            Eligibility::level('BACHELOR OF SCIENCE'),
        );
    }

    public function test_the_qualification_check_ignores_surrounding_whitespace(): void
    {
        $this->assertTrue(Eligibility::qualificationAccepted('   Diploma in Business   '));
        $this->assertTrue(Eligibility::qualificationAccepted("\t Master of Engineering \n"));

        $this->assertSame(
            Eligibility::level('Bachelor of Science'),
            Eligibility::level("  \t Bachelor of Science \r\n "),
        );
    }

    /* ---------------------------------------------------------------- *
     | Missing and nonsensical input
     * ---------------------------------------------------------------- */

    public function test_missing_or_nonsensical_input_is_rejected_without_crashing(): void
    {
        $unusable = [null, '', '   ', "\n\t", '!!!', '12345', 'qwertyuiop', '???'];

        foreach ($unusable as $qualification) {
            $this->assertFalse(
                Eligibility::qualificationAccepted($qualification),
                'Unusable input must be rejected, not accepted by accident.',
            );

            $this->assertNull(
                Eligibility::level($qualification),
                'Unusable input has no comparable level.',
            );

            $this->assertNotSame(
                '',
                trim(Eligibility::qualificationMessage($qualification)),
                'Every rejection must still explain itself to the candidate.',
            );
        }
    }

    /* ---------------------------------------------------------------- *
     | The message shown on rejection
     * ---------------------------------------------------------------- */

    public function test_an_under_qualified_candidate_is_told_what_the_floor_is(): void
    {
        $message = Eligibility::qualificationMessage('Certificate in Welding');

        $this->assertNotSame('', trim($message));
        $this->assertStringContainsString('Diploma', $message);
        $this->assertStringContainsStringIgnoringCase('APEL A', $message);
    }

    public function test_an_unrecognisable_qualification_is_told_how_to_state_it_properly(): void
    {
        $message = Eligibility::qualificationMessage('qwertyuiop');

        $this->assertNotSame('', trim($message));
        $this->assertStringContainsString('qwertyuiop', $message);
        $this->assertStringContainsStringIgnoringCase('could not recognise', $message);
    }

    public function test_an_unrecognisable_qualification_and_an_under_qualified_one_get_different_explanations(): void
    {
        $this->assertNotSame(
            Eligibility::qualificationMessage('qwertyuiop'),
            Eligibility::qualificationMessage('Certificate in Welding'),
            'Being unreadable and being under-qualified are different problems and need different advice.',
        );
    }

    /* ---------------------------------------------------------------- *
     | The floor is configuration, not a literal
     * ---------------------------------------------------------------- */

    public function test_the_default_floor_is_a_diploma(): void
    {
        $this->assertSame(
            Eligibility::level('Diploma in Business'),
            Eligibility::minimumLevel(),
        );
    }

    public function test_raising_the_configured_floor_raises_the_bar_for_everyone(): void
    {
        config(['apel.eligibility.minimum_qualification' => 'bachelor']);

        $this->assertFalse(
            Eligibility::qualificationAccepted('Diploma in Business'),
            'With the floor raised to a Bachelor, a Diploma should no longer be enough.',
        );
        $this->assertFalse(Eligibility::qualificationAccepted('Advanced Diploma in Business'));
        $this->assertTrue(Eligibility::qualificationAccepted('Bachelor of Science'));
        $this->assertTrue(Eligibility::qualificationAccepted('PhD in Computing'));
    }

    public function test_a_nonsensical_configured_floor_falls_back_to_a_diploma_rather_than_letting_everyone_through(): void
    {
        config(['apel.eligibility.minimum_qualification' => 'not-a-real-level']);

        $this->assertSame(Eligibility::level('Diploma in Business'), Eligibility::minimumLevel());
        $this->assertFalse(Eligibility::qualificationAccepted('Certificate in Welding'));
        $this->assertTrue(Eligibility::qualificationAccepted('Diploma in Business'));
    }

    public function test_the_configured_floor_is_read_case_insensitively(): void
    {
        config(['apel.eligibility.minimum_qualification' => 'MASTER']);

        $this->assertSame(Eligibility::level('Master of Engineering'), Eligibility::minimumLevel());
        $this->assertFalse(Eligibility::qualificationAccepted('Bachelor of Science'));
        $this->assertTrue(Eligibility::qualificationAccepted('Master of Engineering'));
    }

    /* ---------------------------------------------------------------- *
     | Age
     * ---------------------------------------------------------------- */

    public function test_the_minimum_age_defaults_to_thirty(): void
    {
        $this->assertSame(30, Eligibility::minimumAge());
    }

    public function test_the_minimum_age_comes_from_configuration_and_is_always_an_integer(): void
    {
        config(['apel.eligibility.minimum_age' => '35']);

        $this->assertSame(35, Eligibility::minimumAge());
    }

    /**
     * The rule is "at least" — a candidate whose birthday has just made them
     * exactly the minimum age is eligible. A `>` instead of a `>=` here would
     * silently reject a whole year's worth of legitimate candidates.
     */
    public function test_a_candidate_who_is_exactly_the_minimum_age_is_old_enough(): void
    {
        $minimum = Eligibility::minimumAge();

        $this->assertSame('pass', $this->ageCriterion($minimum)['status']);
        $this->assertSame('pass', $this->ageCriterion($minimum + 1)['status']);
        $this->assertSame('fail', $this->ageCriterion($minimum - 1)['status']);
    }

    public function test_a_candidate_with_no_stated_age_is_not_treated_as_old_enough(): void
    {
        $this->assertSame('fail', $this->ageCriterion(null)['status']);
        $this->assertSame('fail', $this->ageCriterion(0)['status']);
    }

    /**
     * The APEL A scorecard's age criterion, evaluated against an unsaved model.
     * Nothing here reaches the database.
     */
    private function ageCriterion(?int $age): array
    {
        $application = new Application;
        $application->application_type = 'APEL A';
        $application->age = $age;

        $scorecard = (new ApelDecisionSupportService)->evaluateApelA($application);

        foreach ($scorecard['criteria'] as $criterion) {
            if ($criterion['name'] === 'Minimum age requirement') {
                return $criterion;
            }
        }

        $this->fail('The APEL A scorecard no longer reports a minimum age criterion.');
    }
}
