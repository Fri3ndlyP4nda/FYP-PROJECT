<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Services\ApelDecisionSupportService;

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

        if (!Auth::attempt($credentials)) {
            $this->generateCaptcha();

            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->withInput();
        }

        $user = Auth::user();

        // Check if 2FA was verified within the last 30 minutes
        if ($user->last_2fa_verified_at && $user->last_2fa_verified_at->isAfter(now()->subMinutes(30))) {
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

        User::where('_id', $user->_id)->update([
            'two_factor_code' => Hash::make($otp),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::raw("Your UTM APEL verification code is: {$otp}. This code will expire in 10 minutes.", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('UTM APEL Two-Factor Verification Code');
            });
        } catch (\Exception $e) {
            Log::error('2FA Mail Error: ' . $e->getMessage());
        }

        Auth::logout();

        session([
            '2fa_user_id' => (string) $user->_id,
        ]);

        return redirect()->route('2fa.show');
    }

    public function showTwoFactor()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|digits:6',
        ]);

        $user = User::where('_id', session('2fa_user_id'))->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expired. Please login again.']);
        }

        // Expiry is checked before the code so an expired attempt never reveals
        // whether the submitted digits were otherwise correct.
        if (!$user->two_factor_expires_at || now()->greaterThan($user->two_factor_expires_at)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Verification code expired. Please login again.']);
        }

        // Hash::check is constant-time; the previous loose != compared a secret
        // with PHP's numeric-string juggling rules.
        if (!$user->two_factor_code || !Hash::check($request->two_factor_code, $user->two_factor_code)) {
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

    public function studentDashboard()
    {
        return view('dashboard.student');
    }

    public function evaluatorDashboard()
    {
        $evaluatorId = (string) Auth::id();

        $totalClaims = Application::where('application_type', 'APEL C')
            ->where('evaluator_id', $evaluatorId)
            ->count();

        $gradedCount = AssessmentSubmission::where('graded_by', $evaluatorId)->count();

        $assignedAppIds = Application::where('application_type', 'APEL C')
            ->where('evaluator_id', $evaluatorId)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $pendingCount = empty($assignedAppIds)
            ? 0
            : AssessmentSubmission::whereIn('application_id', $assignedAppIds)
                ->where('status', 'submitted')
                ->count();

        $avgScore = AssessmentSubmission::where('graded_by', $evaluatorId)->avg('score');
        $avgScore = $avgScore ? round($avgScore, 1) : 0;

        return view('dashboard.evaluator', compact(
            'totalClaims',
            'gradedCount',
            'pendingCount',
            'avgScore'
        ));
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
