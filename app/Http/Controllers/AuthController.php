<?php

namespace App\Http\Controllers;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\NextAction;
use App\Domain\Security\AuthLog;
use App\Domain\Security\HumanSignals;
use App\Domain\Security\ProofOfWork;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Services\ApelDecisionSupportService;
use App\Support\ApplicationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Never flashed back to the form.
     *
     * withInput() with no arguments flashes every field, including the
     * password - writing it in cleartext into the session store, which the
     * file driver keeps on disk. Laravel strips these from the *automatic*
     * flash a validation failure performs, but a manual withInput() gets no
     * such treatment.
     */
    private const NEVER_FLASH = ['password', 'password_confirmation', 'current_password'];

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $this->normalizeEmail($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
        ]);

        if ($problem = $this->automationGuard($request)) {
            return back()->withErrors(['pow_answer' => $problem])->withInput($request->except(self::NEVER_FLASH));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        AuthLog::record($request, AuthLog::REGISTERED, $user->email, $user);

        return redirect()->route('login')->with('success', 'Account created successfully. Please log in.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->normalizeEmail($request);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($problem = $this->automationGuard($request)) {
            AuthLog::record($request, AuthLog::SECURITY_CHECK_FAILED, $request->input('email'));

            return back()->withErrors(['pow_answer' => $problem])->withInput($request->except(self::NEVER_FLASH));
        }

        $credentials = [
            'email' => strtolower($request->email),
            'password' => $request->password,
        ];

        if (! Auth::attempt($credentials)) {
            // The address is recorded, never the password that was tried.
            AuthLog::record($request, AuthLog::SIGN_IN_FAILED, $request->input('email'));

            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->withInput($request->except(self::NEVER_FLASH));
        }

        $user = Auth::user();

        // Two-factor disabled: the password and captcha checks are the whole
        // gate, so complete the login here. See config/apel.php.
        if (! config('apel.two_factor.enabled')) {
            $request->session()->regenerate();

            AuthLog::record($request, AuthLog::SIGNED_IN, $user->email, $user);

            return $this->redirectUserByRole($user->role);
        }

        // A recent verification is honoured without issuing a new code.
        $rememberMinutes = (int) config('apel.two_factor.remember_minutes', 30);
        if ($user->last_2fa_verified_at && $user->last_2fa_verified_at->isAfter(now()->subMinutes($rememberMinutes))) {
            $request->session()->regenerate();

            AuthLog::record($request, AuthLog::SIGNED_IN, $user->email, $user, 'Signed in on a remembered two-factor verification');

            return $this->redirectUserByRole($user->role);
        }

        /**
         * random_int() rather than rand(): rand() is Mersenne Twister, seeded
         * once per PHP worker, so an attacker who can trigger OTPs to accounts
         * they control can recover the generator state and predict the code
         * issued to someone else.
         *
         * The code is stored hashed, not in cleartext, so read access to the
         * users collection does not hand over live verification codes.
         */
        $otp = (string) random_int(100000, 999999);

        $codeLifetime = (int) config('apel.two_factor.code_lifetime_minutes', 10);

        User::where('_id', $user->_id)->update([
            'two_factor_code' => Hash::make($otp),
            'two_factor_expires_at' => now()->addMinutes($codeLifetime),
        ]);

        try {
            Mail::raw("Your UTM APEL verification code is: {$otp}. This code will expire in {$codeLifetime} minutes.", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('UTM APEL Two-Factor Verification Code');
            });
        } catch (\Exception $e) {
            Log::error('2FA Mail Error: '.$e->getMessage());
        }

        Auth::logout();

        session([
            '2fa_user_id' => (string) $user->_id,
        ]);

        return redirect()->route('2fa.show');
    }

    public function showTwoFactor()
    {
        if (! config('apel.two_factor.enabled')) {
            return redirect()->route('login');
        }

        if (! session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verifyTwoFactor(Request $request)
    {
        if (! config('apel.two_factor.enabled')) {
            return redirect()->route('login');
        }

        $request->validate([
            'two_factor_code' => 'required|digits:6',
        ]);

        $user = User::where('_id', session('2fa_user_id'))->first();

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expired. Please login again.']);
        }

        // Expiry is checked before the code so an expired attempt never reveals
        // whether the submitted digits were otherwise correct.
        if (! $user->two_factor_expires_at || now()->greaterThan($user->two_factor_expires_at)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Verification code expired. Please login again.']);
        }

        // Hash::check is constant-time; the previous loose != compared a secret
        // with PHP's numeric-string juggling rules.
        if (! $user->two_factor_code || ! Hash::check($request->two_factor_code, $user->two_factor_code)) {
            AuthLog::record($request, AuthLog::TWO_FACTOR_FAILED, $user->email, $user);

            return back()->withErrors([
                'two_factor_code' => 'Invalid verification code.',
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'last_2fa_verified_at' => now(),
        ]);

        session()->forget('2fa_user_id');

        AuthLog::record($request, AuthLog::SIGNED_IN, $user->email, $user, 'Signed in after two-factor verification');

        return $this->redirectUserByRole($user->role);
    }

    private function redirectUserByRole($role)
    {
        return match ($role) {
            'student' => redirect()->route('student.dashboard'),
            'evaluator' => redirect()->route('evaluator.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('login')->withErrors([
                'email' => 'Unauthorized role.',
            ]),
        };
    }

    public function logout(Request $request)
    {
        // Read before the session goes, or there is nobody to attribute it to.
        $user = Auth::user();

        if ($user) {
            AuthLog::record($request, AuthLog::SIGNED_OUT, $user->email, $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }

    /**
     * The candidate's own state, not a description of the product.
     *
     * This passed no data at all - the view was three cards reading "Manage
     * Applications", "Track Review Status", "Stay Informed", which is a feature
     * list, not an answer. Someone signing in wants to know where their
     * application has got to and whether anything is waiting on them, and had
     * to click through to find out.
     */
    public function studentDashboard()
    {
        $viewer = Auth::user();

        $applications = Application::where('user_id', (string) Auth::id())
            ->orderBy('submission_date', 'desc')
            ->get();

        $cases = ApplicationCase::collect($applications, $viewer);

        return view('dashboard.student', [
            'cases' => $cases,
            'yourMove' => ApplicationCase::awaitingViewer($cases),
            'inProgress' => ApplicationCase::elsewhere($cases),
            'closed' => ApplicationCase::closed($cases),
        ]);
    }

    /**
     * What is actually waiting on this evaluator.
     *
     * Two scoping bugs were losing work here. Every query filtered to
     * application_type 'APEL C', so an evaluator assigned an APEL A case saw a
     * dashboard of zeroes; and every one matched evaluator_id alone, so the
     * second of two assigned evaluators saw nothing of the applications they
     * were on. The rest of the evaluator area scopes on
     * `evaluator_id OR evaluator_2_id` with no type filter, and that is what
     * this now does.
     *
     * The counts are kept but demoted. A number cannot be worked; the queue
     * below it can.
     */
    public function evaluatorDashboard()
    {
        $evaluatorId = (string) Auth::id();
        $viewer = Auth::user();

        $mine = function ($query) use ($evaluatorId) {
            $query->where('evaluator_id', $evaluatorId)
                ->orWhere('evaluator_2_id', $evaluatorId);
        };

        $applications = Application::where('status', '!=', 'Draft')
            ->where($mine)
            ->orderBy('submission_date', 'desc')
            ->get();

        $cases = ApplicationCase::collect($applications, $viewer);

        $assignedIds = $applications->map(fn (Application $a) => (string) $a->_id)->all();

        return view('dashboard.evaluator', [
            // Silence from NextAction means the stage is not the viewer's move.
            'waitingOnMe' => ApplicationCase::awaitingViewer($cases),
            'withOthers' => ApplicationCase::elsewhere($cases),
            'closed' => ApplicationCase::closed($cases),
            'assignedCount' => $applications->count(),
            'gradedCount' => AssessmentSubmission::where('graded_by', $evaluatorId)->count(),
            'awaitingGrading' => empty($assignedIds) ? 0 : AssessmentSubmission::whereIn('application_id', $assignedIds)
                ->where('status', 'submitted')
                ->count(),
        ]);
    }

    /**
     * The registry's overview.
     *
     * What stood here was five totals and a chart: how many applications
     * exist, how many of each type, how many were approved. None of that is
     * work. The officer opening this wants to know what is stuck and what is
     * waiting on them, so the counts that carry an action come first and the
     * inventory is demoted to a footnote.
     *
     * Approval counts read from the stage rather than final_decision and
     * credit_decision. The two agree today, but the stage is the field the
     * workflow actually maintains and the other two are written by one
     * controller each.
     */
    /**
     * Pending and failed queued jobs.
     *
     * Counted defensively: a missing collection or an unreachable queue store
     * must not be the reason the dashboard 500s, so a failure here reports as
     * unknown rather than throwing.
     *
     * @return array{pending:?int,failed:?int}
     */
    private static function queueHealth(): array
    {
        $count = function (string $collection): ?int {
            try {
                return DB::connection('mongodb')->getCollection($collection)->countDocuments();
            } catch (\Throwable) {
                return null;
            }
        };

        return [
            'pending' => $count('jobs'),
            'failed' => $count('failed_jobs'),
        ];
    }

    public function adminDashboard()
    {
        $applications = Application::get();

        $approved = $applications->filter(
            fn (Application $a) => ApplicationCase::stageOf($a) === ApelStage::APPROVED
        );

        /*
         | Notifications are queued, so they only send while a worker is
         | running. If one dies, mail stops leaving and nothing anywhere says
         | so - candidates simply never hear back, and the first anyone knows
         | is a complaint. Surfacing the backlog turns a silent failure into a
         | number on the screen the registry already opens every morning.
         */
        $queue = self::queueHealth();

        return view('dashboard.admin', [
            'queue' => $queue,
            'workflowMetrics' => app(ApelDecisionSupportService::class)->workflowMetrics(),
            'activityLogs' => ActivityLog::orderBy('created_at', 'desc')->limit(8)->get(),

            'totalApplications' => $applications->count(),
            'apelACount' => $applications->where('application_type', 'APEL A')->count(),
            'apelCCount' => $applications->where('application_type', 'APEL C')->count(),
            'apelAApproved' => $approved->where('application_type', 'APEL A')->count(),
            'apelCApproved' => $approved->where('application_type', 'APEL C')->count(),
        ]);
    }

    /**
     * The anti-automation guard, replacing the arithmetic captcha.
     *
     * That captcha printed "3 + 5 = ?" into the page and compared the answer
     * against the session, so a script solved it with one regular expression
     * and an addition - and a script that did not even parse it had a 1-in-17
     * chance of guessing, the sum of two digits from 1 to 9 having only
     * seventeen possible values.
     *
     * Returns null when the submission passes, or an error message when it
     * does not. The message is deliberately the same for every kind of
     * failure: telling a bot whether it tripped the honeypot, submitted too
     * fast, or failed the proof of work tells it exactly what to change.
     */
    private function automationGuard(Request $request): ?string
    {
        $generic = 'The security check did not pass. Please reload the page and try again.';

        if (HumanSignals::check($request) !== null) {
            return $generic;
        }

        $failure = ProofOfWork::verify([
            'salt' => $request->input('pow_salt'),
            'target' => $request->input('pow_target'),
            'difficulty' => $request->input('pow_difficulty'),
            'expires' => $request->input('pow_expires'),
            'signature' => $request->input('pow_signature'),
            'answer' => $request->input('pow_answer'),
        ]);

        return $failure === null ? null : $generic;
    }
}
