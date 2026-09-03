<?php

namespace App\Http\Controllers;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\NextAction;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Services\ApelDecisionSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister()
    {
        $this->generateCaptcha();

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
            'captcha_answer' => 'required',
        ]);

        if ((int) $request->captcha_answer !== (int) session('captcha_answer')) {
            $this->generateCaptcha();

            return back()
                ->withErrors(['captcha_answer' => 'Incorrect security check answer.'])
                ->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        return redirect()->route('login')->with('success', 'Account created successfully. Please log in.');
    }

    public function showLogin()
    {
        $this->generateCaptcha();

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->normalizeEmail($request);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'captcha_answer' => 'required',
        ]);

        if ((int) $request->captcha_answer !== (int) session('captcha_answer')) {
            $this->generateCaptcha();

            return back()
                ->withErrors(['captcha_answer' => 'Incorrect security check answer.'])
                ->withInput();
        }

        $credentials = [
            'email' => strtolower($request->email),
            'password' => $request->password,
        ];

        if (! Auth::attempt($credentials)) {
            $this->generateCaptcha();

            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->withInput();
        }

        $user = Auth::user();

        // Two-factor disabled: the password and captcha checks are the whole
        // gate, so complete the login here. See config/apel.php.
        if (! config('apel.two_factor.enabled')) {
            $request->session()->regenerate();

            return $this->redirectUserByRole($user->role);
        }

        // A recent verification is honoured without issuing a new code.
        $rememberMinutes = (int) config('apel.two_factor.remember_minutes', 30);
        if ($user->last_2fa_verified_at && $user->last_2fa_verified_at->isAfter(now()->subMinutes($rememberMinutes))) {
            $request->session()->regenerate();

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

        // Resolved once here rather than per-row in Blade, so the view stays a
        // presentation of state instead of a place where workflow rules live.
        $cases = $applications->map(function (Application $application) use ($viewer) {
            $stage = self::stageOf($application);
            $type = (string) $application->application_type;

            return [
                'application' => $application,
                'stage' => $stage,
                'type' => $type,
                'action' => NextAction::for($application, $viewer),
                'rail' => $stage?->rail($type) ?? [],
                'progress' => $stage?->progress($type) ?? 0,
                'explanation' => $stage?->studentExplanation($type) ?? '',
            ];
        });

        return view('dashboard.student', [
            'cases' => $cases,
            'yourMove' => $cases->filter(fn ($c) => $c['stage']?->awaitsStudent())->values(),
            'inProgress' => $cases->filter(fn ($c) => $c['stage'] && ! $c['stage']->awaitsStudent() && ! $c['stage']->isTerminal())->values(),
            'closed' => $cases->filter(fn ($c) => $c['stage']?->isTerminal())->values(),
        ]);
    }

    /**
     * Read the stage without going through the accessor.
     *
     * mongodb/laravel-mongodb resolves a method whose name matches a field as
     * an embedded relation before it checks the attributes, so Application has
     * no usable stage() accessor - reading it throws.
     */
    private static function stageOf(Application $application): ?ApelStage
    {
        $raw = $application->getAttributes()['stage'] ?? null;

        return $raw ? ApelStage::tryFrom((string) $raw) : null;
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

        $cases = $applications->map(function (Application $application) use ($viewer) {
            $stage = self::stageOf($application);

            return [
                'application' => $application,
                'stage' => $stage,
                'type' => (string) $application->application_type,
                'action' => NextAction::for($application, $viewer),
            ];
        });

        // Silence from NextAction means this stage is not the viewer's move.
        $waitingOnMe = $cases->filter(
            fn ($c) => $c['stage'] && ! $c['stage']->isTerminal() && $c['action'] !== null
        )->values();

        $assignedIds = $applications->map(fn (Application $a) => (string) $a->_id)->all();

        return view('dashboard.evaluator', [
            'waitingOnMe' => $waitingOnMe,
            'withOthers' => $cases->filter(
                fn ($c) => $c['stage'] && ! $c['stage']->isTerminal() && $c['action'] === null
            )->values(),
            'closed' => $cases->filter(fn ($c) => $c['stage']?->isTerminal())->values(),
            'assignedCount' => $applications->count(),
            'gradedCount' => AssessmentSubmission::where('graded_by', $evaluatorId)->count(),
            'awaitingGrading' => empty($assignedIds) ? 0 : AssessmentSubmission::whereIn('application_id', $assignedIds)
                ->where('status', 'submitted')
                ->count(),
        ]);
    }

    public function adminDashboard()
    {
        $totalApplications = Application::count();
        $apelACount = Application::where('application_type', 'APEL A')->count();
        $apelCCount = Application::where('application_type', 'APEL C')->count();
        $apelAApproved = Application::where('application_type', 'APEL A')
            ->where('final_decision', 'approved')
            ->count();
        $apelCApproved = Application::where('application_type', 'APEL C')
            ->where('credit_decision', 'approved')
            ->count();

        $activityLogs = ActivityLog::orderBy('created_at', 'desc')
            ->limit(8)
            ->get();
        $workflowMetrics = app(ApelDecisionSupportService::class)->workflowMetrics();

        return view('dashboard.admin', compact(
            'totalApplications',
            'apelACount',
            'apelCCount',
            'apelAApproved',
            'apelCApproved',
            'activityLogs',
            'workflowMetrics'
        ));
    }

    private function generateCaptcha()
    {
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);

        session([
            'captcha_question' => "$num1 + $num2 = ?",
            'captcha_answer' => $num1 + $num2,
        ]);
    }
}
