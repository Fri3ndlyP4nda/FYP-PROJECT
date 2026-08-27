<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\FeatureTestCase;

/**
 * The front door: login, registration and password recovery.
 *
 * Several of these are regression guards for defects that were live in this
 * application, and they are marked as such:
 *
 *  - the login error used to distinguish "no such account" from "wrong
 *    password", turning the form into a membership oracle;
 *  - registration validated uniqueness against the raw request value, so
 *    ARIF@example.com sailed past unique:users,email and created a second
 *    document alongside arif@example.com (MongoDB string equality is
 *    case-sensitive);
 *  - PasswordResetToken::$created_at came back as a raw BSON UTCDateTime that
 *    Carbon::parse() could not read, so *every* reset threw before the token
 *    was even compared.
 */
class AuthFlowTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The credential endpoints are throttled through the cache store. Start
        // every test from an empty bucket so no test can inherit another's
        // attempt count.
        $this->clearRateLimits();
    }

    private function clearRateLimits(): void
    {
        Cache::flush();
    }

    /**
     * The captcha is an arithmetic question generated into the session by
     * showLogin()/showRegister(). Fetching the form is how a real browser learns
     * the expected answer, so the tests do exactly that rather than faking the
     * session value — that way the test breaks if the question stops being
     * issued at all.
     */
    private function captchaAnswerFrom(string $formRoute): int
    {
        $this->get(route($formRoute))->assertOk();

        return (int) session('captcha_answer');
    }

    private function loginPayload(string $email, string $password): array
    {
        return [
            'email' => $email,
            'password' => $password,
            'captcha_answer' => $this->captchaAnswerFrom('login'),
        ];
    }

    // ---------------------------------------------------------------- login

    public function test_a_student_signing_in_with_the_right_credentials_lands_on_the_student_dashboard(): void
    {
        $student = $this->makeStudent(['password' => Hash::make('TestPassword123')]);

        $response = $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload($student->email, 'TestPassword123'));

        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($student);
    }

    public function test_an_evaluator_and_an_admin_each_land_on_their_own_dashboard(): void
    {
        $evaluator = $this->makeEvaluator(['password' => Hash::make('TestPassword123')]);

        $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload($evaluator->email, 'TestPassword123'))
            ->assertRedirect(route('evaluator.dashboard'));
        $this->assertAuthenticatedAs($evaluator);

        $this->post(route('logout'));
        $this->assertGuest();
        $this->clearRateLimits();

        $admin = $this->makeAdmin(['password' => Hash::make('TestPassword123')]);

        $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload($admin->email, 'TestPassword123'))
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_signing_in_with_the_wrong_password_is_rejected_without_saying_the_account_exists(): void
    {
        $student = $this->makeStudent([
            'email' => 'known@apel.test',
            'password' => Hash::make('TestPassword123'),
        ]);

        $wrongPassword = $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload($student->email, 'NotThePassword1'));

        $wrongPassword->assertRedirect(route('login'));
        $wrongPassword->assertSessionHasErrors('email');
        $this->assertGuest();

        $knownMessage = session('errors')->first('email');

        $this->clearRateLimits();

        $unknownAccount = $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload('nobody@apel.test', 'NotThePassword1'));

        $unknownAccount->assertSessionHasErrors('email');
        $unknownMessage = session('errors')->first('email');

        $this->assertSame(
            'Invalid email or password.',
            $knownMessage,
            'The login failure message must not name which half of the credentials was wrong.',
        );
        $this->assertSame(
            $knownMessage,
            $unknownMessage,
            'A registered address and an unregistered one must produce the identical error, '
            . 'or the login form becomes a membership oracle for the whole user base.',
        );
    }

    public function test_signing_in_without_answering_the_captcha_is_rejected(): void
    {
        $student = $this->makeStudent(['password' => Hash::make('TestPassword123')]);

        $this->captchaAnswerFrom('login');

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'TestPassword123',
        ]);

        $response->assertSessionHasErrors('captcha_answer');
        $this->assertGuest();
    }

    public function test_correct_credentials_with_a_wrong_captcha_answer_still_do_not_sign_anyone_in(): void
    {
        $student = $this->makeStudent(['password' => Hash::make('TestPassword123')]);

        $answer = $this->captchaAnswerFrom('login');

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'TestPassword123',
            // The question is "a + b = ?" with a and b each between 1 and 9, so
            // the sum can never be this.
            'captcha_answer' => $answer + 100,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['captcha_answer' => 'Incorrect security check answer.']);
        $this->assertGuest();
    }

    public function test_the_login_endpoint_throttles_repeated_failures_for_the_same_account(): void
    {
        $this->makeStudent([
            'email' => 'sprayed@apel.test',
            'password' => Hash::make('TestPassword123'),
        ]);

        // AppServiceProvider::configureRateLimiters() allows five attempts a
        // minute against one identity (sha1(email|ip)).
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))
                ->post(route('login.submit'), $this->loginPayload('sprayed@apel.test', 'WrongPassword1'))
                ->assertRedirect(route('login'));
        }

        $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload('sprayed@apel.test', 'WrongPassword1'))
            ->assertStatus(429);

        $this->assertGuest();
    }

    // --------------------------------------------------------- registration

    public function test_registration_always_creates_a_student_even_when_another_role_is_posted(): void
    {
        $response = $this->from(route('register'))->post(route('register.submit'), [
            'name' => 'Escalation Attempt',
            'email' => 'escalate@apel.test',
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
            'role' => 'admin',
            'captcha_answer' => $this->captchaAnswerFrom('register'),
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'escalate@apel.test')->first();

        $this->assertNotNull($created, 'Registration should have created the account.');
        $this->assertSame(
            'student',
            $created->role,
            'Self-registration hardcodes the student role; a posted role must never be honoured.',
        );
        $this->assertSame(0, User::where('role', 'admin')->count());
    }

    public function test_registering_an_address_that_differs_only_in_case_is_rejected_as_a_duplicate(): void
    {
        $this->makeStudent(['email' => 'arif@apel.test']);

        $response = $this->from(route('register'))->post(route('register.submit'), [
            'name' => 'Arif Again',
            'email' => 'ARIF@apel.test',
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
            'captcha_answer' => $this->captchaAnswerFrom('register'),
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            User::where('email', 'arif@apel.test')->count(),
            'The address is stored lowercased, so an uppercase variant must not create a second document. '
            . 'MongoDB string equality is case-sensitive and unique:users,email used to run on the raw input.',
        );
        $this->assertSame(0, User::where('email', 'ARIF@apel.test')->count());
        $this->assertSame(1, User::count());
    }

    public function test_registration_normalises_the_stored_address_to_lower_case(): void
    {
        $this->from(route('register'))->post(route('register.submit'), [
            'name' => 'Mixed Case',
            'email' => 'MiXeD@apel.test',
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
            'captcha_answer' => $this->captchaAnswerFrom('register'),
        ])->assertRedirect(route('login'));

        $this->assertSame(1, User::where('email', 'mixed@apel.test')->count());
    }

    // -------------------------------------------------------- password reset

    public function test_requesting_a_reset_link_stores_a_hashed_token_and_never_the_plaintext(): void
    {
        Mail::fake();

        $student = $this->makeStudent(['email' => 'recover@apel.test']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $student->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('success');

        $plainToken = $this->plainTokenFromMail($student->email);

        $record = PasswordResetToken::where('email', 'recover@apel.test')->first();

        $this->assertNotNull($record, 'No reset token row was written.');
        $this->assertNotSame(
            $plainToken,
            $record->token,
            'The reset token is stored in the clear — read access to the collection would hand over live reset links.',
        );
        $this->assertTrue(
            Hash::check($plainToken, $record->token),
            'The stored value must be a hash of the token that was emailed.',
        );
    }

    public function test_requesting_a_reset_link_for_an_unknown_address_looks_exactly_like_a_known_one(): void
    {
        Mail::fake();

        $student = $this->makeStudent(['email' => 'recover@apel.test']);

        $known = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $student->email]);

        $knownMessage = session('success');

        $this->clearRateLimits();

        $unknown = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ghost@apel.test']);

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $unknown->assertRedirect(route('password.request'));
        $unknown->assertSessionHasNoErrors();

        $this->assertSame(
            $knownMessage,
            session('success'),
            'A registered and an unregistered address must produce byte-identical responses, '
            . 'or /forgot-password enumerates the user base.',
        );

        $this->assertSame(
            0,
            PasswordResetToken::where('email', 'ghost@apel.test')->count(),
            'No token should be minted for an address with no account.',
        );
    }

    public function test_a_reset_actually_changes_the_password_and_the_token_works_only_once(): void
    {
        Mail::fake();

        $student = $this->makeStudent([
            'email' => 'recover@apel.test',
            'password' => Hash::make('TestPassword123'),
        ]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $student->email]);

        $plainToken = $this->plainTokenFromMail($student->email);

        $this->clearRateLimits();

        $this->from(route('password.reset', ['token' => $plainToken]))
            ->post(route('password.update'), [
                'email' => $student->email,
                'token' => $plainToken,
                'password' => 'BrandNewPass9',
                'password_confirmation' => 'BrandNewPass9',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $fresh = $student->fresh();

        $this->assertTrue(Hash::check('BrandNewPass9', $fresh->password), 'The new password was not saved.');
        $this->assertFalse(Hash::check('TestPassword123', $fresh->password), 'The old password still works.');

        $this->assertSame(
            0,
            PasswordResetToken::where('email', 'recover@apel.test')->count(),
            'The token row must be consumed by a successful reset.',
        );

        $this->clearRateLimits();

        $replay = $this->from(route('password.reset', ['token' => $plainToken]))
            ->post(route('password.update'), [
                'email' => $student->email,
                'token' => $plainToken,
                'password' => 'SecondAttempt7',
                'password_confirmation' => 'SecondAttempt7',
            ]);

        $replay->assertSessionHasErrors('email');
        $this->assertTrue(
            Hash::check('BrandNewPass9', $student->fresh()->password),
            'A replayed reset token changed the password a second time.',
        );
    }

    public function test_a_reset_token_older_than_an_hour_is_rejected_rather_than_blowing_up(): void
    {
        $student = $this->makeStudent([
            'email' => 'stale@apel.test',
            'password' => Hash::make('TestPassword123'),
        ]);

        $plainToken = 'a-token-that-was-issued-far-too-long-ago';

        // Written straight to the collection so the controller reads created_at
        // back out of BSON, which is where Carbon::parse() used to throw.
        PasswordResetToken::create([
            'email' => 'stale@apel.test',
            'token' => Hash::make($plainToken),
            'created_at' => now()->subMinutes(90),
        ]);

        $response = $this->from(route('password.reset', ['token' => $plainToken]))
            ->post(route('password.update'), [
                'email' => $student->email,
                'token' => $plainToken,
                'password' => 'BrandNewPass9',
                'password_confirmation' => 'BrandNewPass9',
            ]);

        $response->assertSessionHasErrors(['email' => 'This reset link has expired.']);

        $this->assertTrue(
            Hash::check('TestPassword123', $student->fresh()->password),
            'An expired token still changed the password.',
        );
        $this->assertSame(
            0,
            PasswordResetToken::where('email', 'stale@apel.test')->count(),
            'An expired token row should be cleaned up when it is refused.',
        );
    }

    public function test_a_reset_token_just_inside_the_hour_is_still_accepted(): void
    {
        $student = $this->makeStudent([
            'email' => 'fresh@apel.test',
            'password' => Hash::make('TestPassword123'),
        ]);

        $plainToken = 'a-token-issued-fifty-nine-minutes-ago';

        PasswordResetToken::create([
            'email' => 'fresh@apel.test',
            'token' => Hash::make($plainToken),
            'created_at' => now()->subMinutes(59),
        ]);

        $this->from(route('password.reset', ['token' => $plainToken]))
            ->post(route('password.update'), [
                'email' => $student->email,
                'token' => $plainToken,
                'password' => 'BrandNewPass9',
                'password_confirmation' => 'BrandNewPass9',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('BrandNewPass9', $student->fresh()->password));
    }

    public function test_a_reset_with_the_wrong_token_is_refused(): void
    {
        $student = $this->makeStudent([
            'email' => 'recover@apel.test',
            'password' => Hash::make('TestPassword123'),
        ]);

        PasswordResetToken::create([
            'email' => 'recover@apel.test',
            'token' => Hash::make('the-real-token'),
            'created_at' => now(),
        ]);

        $this->from(route('password.reset', ['token' => 'a-guess']))
            ->post(route('password.update'), [
                'email' => $student->email,
                'token' => 'a-guess',
                'password' => 'BrandNewPass9',
                'password_confirmation' => 'BrandNewPass9',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('TestPassword123', $student->fresh()->password));
    }

    // ------------------------------------------------------------------ 2fa

    public function test_the_two_factor_page_redirects_to_login_while_two_factor_is_disabled(): void
    {
        // Compared loosely on purpose: PHPUnit turns <env value="false"/> into an
        // empty string before putenv(), so the config value here is '' rather
        // than a real boolean. Either way the controller's `! config(...)` gate
        // reads it as disabled, which is what this test is about.
        $this->assertFalse(
            (bool) config('apel.two_factor.enabled'),
            'This environment is meant to run with APEL_2FA_ENABLED=false.',
        );

        $this->get(route('2fa.show'))->assertRedirect(route('login'));
    }

    public function test_a_successful_login_completes_immediately_instead_of_stopping_at_a_verification_step(): void
    {
        Mail::fake();

        $student = $this->makeStudent(['password' => Hash::make('TestPassword123')]);

        $response = $this->from(route('login'))
            ->post(route('login.submit'), $this->loginPayload($student->email, 'TestPassword123'));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);

        $this->assertNull(session('2fa_user_id'), 'No pending verification should be parked in the session.');
        $this->assertNull(
            $student->fresh()->two_factor_code,
            'No one-time code should be issued while two-factor is disabled.',
        );

        Mail::assertNothingSent();
    }

    // -------------------------------------------------------------- helpers

    /**
     * The plaintext token only ever exists inside the emailed link — by design.
     */
    private function plainTokenFromMail(string $email): string
    {
        $link = null;

        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use (&$link, $email) {
            if ($mail->hasTo($email)) {
                $link = $mail->resetLink;

                return true;
            }

            return false;
        });

        $this->assertNotNull($link, 'The reset email carried no link.');
        $this->assertSame(1, preg_match('#/reset-password/([^/?]+)#', $link, $matches));

        return $matches[1];
    }
}
