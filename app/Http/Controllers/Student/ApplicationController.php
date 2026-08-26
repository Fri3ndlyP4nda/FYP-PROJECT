<?php

namespace App\Http\Controllers\Student;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\Eligibility;
use App\Domain\Apel\StageMachine;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Programme;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\GenericQueueMail;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::where('user_id', (string) Auth::id())
            ->orderBy('submission_date', 'desc')
            ->get();

        return view('student.applications.index', compact('applications'));
    }

    public function create()
    {
        $programmes = Programme::where('status', 'active')->get();
        $courses = Course::where('status', 'active')->get();

        return view('student.applications.create', compact('programmes', 'courses'));
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('submit_type') === 'draft';

        if ($isDraft) {
            $request->validate([
                'application_type' => 'required|in:APEL A,APEL C',
                'program_applied' => 'nullable|string|max:255',
                'course_id' => 'nullable|string',
            ]);
        } else {
            $request->validate([
                'application_type' => 'required|in:APEL A,APEL C',
                'program_applied' => 'required_if:application_type,APEL A|nullable|string|max:255',
                'course_id' => 'required_if:application_type,APEL C|nullable|string',

                'highest_qualification' => 'required_if:application_type,APEL A|nullable|string|max:255',
                'current_job' => 'required_if:application_type,APEL A|nullable|string|max:255',
                'working_experience_years' => 'required_if:application_type,APEL A|nullable|numeric|min:0|max:60',
                'working_experience_details' => 'required_if:application_type,APEL A|nullable|string|max:3000',
                'reason_applying' => 'required_if:application_type,APEL A|nullable|string|max:3000',

                // New APEL C Form validation
                'pre_app_data' => 'required_if:application_type,APEL C|array',
                'self_assessment' => 'required_if:application_type,APEL C|array',
                'target_semester' => 'nullable|string',

                'evidence_file.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
                'portfolio_file.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
                'age' => 'required_if:application_type,APEL A|nullable|numeric|min:18|max:100',
                'university_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'ic_no' => 'required_if:application_type,APEL A|nullable|string|max:30',
            ]);
        }

        $isApelA = $request->application_type === 'APEL A';
        $isApelC = $request->application_type === 'APEL C';

        if ($isApelA && !$isDraft) {
            // 1. Age check
            $age = (int) $request->age;
            if ($age < Eligibility::minimumAge()) {
                return back()->withErrors([
                    'eligibility' => 'APEL A requires candidates to be at least ' . Eligibility::minimumAge() . ' years of age at the time of application.'
                ])->withInput();
            }

            // 2. IC check
            $ic = str_replace('-', '', $request->ic_no ?? '');
            if (!preg_match('/^\d{12}$/', $ic)) {
                return back()->withErrors([
                    'eligibility' => 'APEL A candidates must be Malaysian Citizens with a valid 12-digit Identity Card (IC) number.'
                ])->withInput();
            }

            /*
             | 3. Qualification floor.
             |
             | This was stripos($hq, 'diploma') !== 0 — the text had to *begin*
             | with "Diploma", so "Bachelor of Science" and "Advanced Diploma"
             | were both rejected as insufficient. The rule is a minimum, so it
             | is now expressed as one, from config/apel.php.
             */
            if (! Eligibility::qualificationAccepted($request->highest_qualification)) {
                return back()->withErrors([
                    'eligibility' => Eligibility::qualificationMessage($request->highest_qualification),
                ])->withInput();
            }
        }

        if ($isApelC && !$isDraft) {
            $preAppData = $request->pre_app_data;

            // 1. Diploma Check
            $qualification = $preAppData['personal_particulars']['highest_qualification'] ?? '';
            $eligibleQualifications = ['Diploma', 'Bachelor', 'Master', 'PhD'];
            if (!in_array($qualification, $eligibleQualifications)) {
                return back()->withErrors([
                    'eligibility' => 'APEL C requires at least a Diploma qualification.'
                ])->withInput();
            }

            // 2. Work Experience Check (3 years minimum)
            $totalMonths = 0;
            $experience = $preAppData['experiential_learning'] ?? [];
            foreach ($experience as $job) {
                if (empty($job['time_from']) || empty($job['time_to'])) continue;
                try {
                    $from = \Carbon\Carbon::parse($job['time_from']);
                    $toStr = strtolower(trim($job['time_to']));
                    $to = ($toStr === 'current' || $toStr === 'present') ? now() : \Carbon\Carbon::parse($job['time_to']);
                    $totalMonths += $from->diffInMonths($to);
                } catch (\Exception $e) {
                    // Fallback: assume 12 months
                    $totalMonths += 12;
                }
            }
            if (($totalMonths / 12) < 3.0) {
                return back()->withErrors([
                    'eligibility' => 'APEL C requires a minimum of 3 years of work experience in a related field. Currently calculated: ' . round($totalMonths / 12, 1) . ' years.'
                ])->withInput();
            }

            // 3. Certificate Age Check (maximum 5 years old)
            $certs = $preAppData['formal_learning'] ?? [];
            $currentYear = (int) date('Y');
            foreach ($certs as $cert) {
                $year = (int) filter_var($cert['year_awarded'], FILTER_SANITIZE_NUMBER_INT);
                if ($year > 0 && ($currentYear - $year) > 5) {
                    return back()->withErrors([
                        'eligibility' => "Certificate '{$cert['title_of_certification']}' was awarded in {$year}, which is older than the allowed 5 years validity."
                    ])->withInput();
                }
            }
        }

        $selectedCourse = null;

        if ($isApelC && $request->filled('course_id')) {
            $selectedCourse = Course::where('_id', $request->course_id)->first();
        }

        $evidenceFiles = [];
        $portfolioFiles = [];

        if ($isApelC && $request->hasFile('evidence_file')) {
            foreach ($request->file('evidence_file') as $file) {
                $evidenceFiles[] = [
                    'path' => $file->store('apel_c/evidence', 'private'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        if ($isApelC && $request->hasFile('portfolio_file')) {
            foreach ($request->file('portfolio_file') as $file) {
                $portfolioFiles[] = [
                    'path' => $file->store('apel_c/portfolio', 'private'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        $programApplied = '';
        if ($isApelA) {
            $programApplied = $request->program_applied ?? '';
        } elseif ($selectedCourse) {
            $programApplied = $selectedCourse->course_name . ' (' . $selectedCourse->course_code . ')';
        }

        $application = Application::create([
            'user_id' => (string) Auth::id(),
            'student_id' => (string) Auth::id(),
            'application_type' => $request->application_type,

            'program_applied' => $programApplied,

            'submission_date' => now(),

            'age' => $isApelA ? $request->age : null,
            'university_name' => $isApelA ? $request->university_name : null,
            'company_name' => $isApelA ? $request->company_name : null,
            'ic_no' => $isApelA ? $request->ic_no : null,

            /*
             | Every application is born a draft and reaches its real stage
             | through StageMachine below. Writing the submitted stage here
             | would skip the history entry and the legality check.
             |
             | payment_status is no longer set at creation. It used to be
             | 'pending' from the first keystroke, which is what made the
             | applications list tell APEL C candidates to "Upload Receipt
             | Here" before an advisor had recommended them — and before the
             | faculty knew whether a fee was owed at all.
             */
            'stage' => ApelStage::DRAFT->value,
            'stage_entered_at' => now(),
            'status' => ApelStage::DRAFT->label($request->application_type),
            'status_updated_at' => now(),

            'payment_type' => $isApelA ? 'APEL A Processing Fee' : 'APEL C Processing / Credit Transfer Fee',
            'payment_reference' => null,
            'payment_remarks' => null,
            'payment_verified_at' => null,
            'payment_receipt' => null,

            'evaluator_id' => null,
            'assigned_at' => null,

            'credit_course_code' => $selectedCourse ? $selectedCourse->course_code : null,
            'credit_course_name' => $selectedCourse ? $selectedCourse->course_name : null,

            'admission_remarks' => null,
            'final_decision_remarks' => null,
            'credit_remarks' => null,
            'credit_hours_approved' => null,

            'highest_qualification' => $isApelA ? $request->highest_qualification : null,
            'current_job' => $isApelA ? $request->current_job : null,
            'working_experience_years' => $isApelA ? $request->working_experience_years : null,
            'working_experience_details' => $isApelA ? $request->working_experience_details : null,
            'reason_applying' => $isApelA ? $request->reason_applying : null,

            // Save new structured APEL C fields
            'pre_app_data' => $isApelC ? $request->pre_app_data : null,
            'self_assessment' => $isApelC ? $request->self_assessment : null,
            'target_semester' => $isApelC ? $request->target_semester : null,
            'target_year' => $isApelA ? now()->format('Y') : null,

            'evidence_file' => $isApelC ? $evidenceFiles : [],
            'portfolio_file' => $isApelC ? $portfolioFiles : [],
        ]);

        if (!$isDraft) {
            $application = $this->submitForReview($application);
        }

        $msg = $isDraft
            ? 'Application draft saved successfully.'
            : $this->submissionMessage($application);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'application_id' => (string) $application->_id
            ]);
        }
        return redirect()->route('student.applications.index')
            ->with('success', $msg);
    }

    public function edit($id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->where('stage', ApelStage::DRAFT->value)
            ->firstOrFail();

        $programmes = Programme::where('status', 'active')->get();
        $courses = Course::where('status', 'active')->get();

        return view('student.applications.edit', compact('application', 'programmes', 'courses'));
    }

    public function update(Request $request, $id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->where('stage', ApelStage::DRAFT->value)
            ->firstOrFail();

        $isDraft = $request->input('submit_type') === 'draft';

        if ($isDraft) {
            $request->validate([
                'application_type' => 'required|in:APEL A,APEL C',
                'program_applied' => 'nullable|string|max:255',
                'course_id' => 'nullable|string',
            ]);
        } else {
            $request->validate([
                'application_type' => 'required|in:APEL A,APEL C',
                'program_applied' => 'required_if:application_type,APEL A|nullable|string|max:255',
                'course_id' => 'required_if:application_type,APEL C|nullable|string',

                'highest_qualification' => 'required_if:application_type,APEL A|nullable|string|max:255',
                'current_job' => 'required_if:application_type,APEL A|nullable|string|max:255',
                'working_experience_years' => 'required_if:application_type,APEL A|nullable|numeric|min:0|max:60',
                'working_experience_details' => 'required_if:application_type,APEL A|nullable|string|max:3000',
                'reason_applying' => 'required_if:application_type,APEL A|nullable|string|max:3000',

                // New APEL C Form validation
                'pre_app_data' => 'required_if:application_type,APEL C|array',
                'self_assessment' => 'required_if:application_type,APEL C|array',
                'target_semester' => 'nullable|string',

                'evidence_file.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
                'portfolio_file.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
                'age' => 'required_if:application_type,APEL A|nullable|numeric|min:18|max:100',
                'university_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'ic_no' => 'required_if:application_type,APEL A|nullable|string|max:30',
            ]);
        }

        $isApelA = $request->application_type === 'APEL A';
        $isApelC = $request->application_type === 'APEL C';

        if ($isApelA && !$isDraft) {
            // 1. Age check
            $age = (int) $request->age;
            if ($age < Eligibility::minimumAge()) {
                return back()->withErrors([
                    'eligibility' => 'APEL A requires candidates to be at least ' . Eligibility::minimumAge() . ' years of age at the time of application.'
                ])->withInput();
            }

            // 2. IC check
            $ic = str_replace('-', '', $request->ic_no ?? '');
            if (!preg_match('/^\d{12}$/', $ic)) {
                return back()->withErrors([
                    'eligibility' => 'APEL A candidates must be Malaysian Citizens with a valid 12-digit Identity Card (IC) number.'
                ])->withInput();
            }

            /*
             | 3. Qualification floor.
             |
             | This was stripos($hq, 'diploma') !== 0 — the text had to *begin*
             | with "Diploma", so "Bachelor of Science" and "Advanced Diploma"
             | were both rejected as insufficient. The rule is a minimum, so it
             | is now expressed as one, from config/apel.php.
             */
            if (! Eligibility::qualificationAccepted($request->highest_qualification)) {
                return back()->withErrors([
                    'eligibility' => Eligibility::qualificationMessage($request->highest_qualification),
                ])->withInput();
            }
        }

        if ($isApelC && !$isDraft) {
            $preAppData = $request->pre_app_data;

            // 1. Diploma Check
            $qualification = $preAppData['personal_particulars']['highest_qualification'] ?? '';
            $eligibleQualifications = ['Diploma', 'Bachelor', 'Master', 'PhD'];
            if (!in_array($qualification, $eligibleQualifications)) {
                return back()->withErrors([
                    'eligibility' => 'APEL C requires at least a Diploma qualification.'
                ])->withInput();
            }

            // 2. Work Experience Check (3 years minimum)
            $totalMonths = 0;
            $experience = $preAppData['experiential_learning'] ?? [];
            foreach ($experience as $job) {
                if (empty($job['time_from']) || empty($job['time_to'])) continue;
                try {
                    $from = \Carbon\Carbon::parse($job['time_from']);
                    $toStr = strtolower(trim($job['time_to']));
                    $to = ($toStr === 'current' || $toStr === 'present') ? now() : \Carbon\Carbon::parse($job['time_to']);
                    $totalMonths += $from->diffInMonths($to);
                } catch (\Exception $e) {
                    $totalMonths += 12;
                }
            }
            if (($totalMonths / 12) < 3.0) {
                return back()->withErrors([
                    'eligibility' => 'APEL C requires a minimum of 3 years of work experience in a related field. Currently calculated: ' . round($totalMonths / 12, 1) . ' years.'
                ])->withInput();
            }

            // 3. Certificate Age Check (maximum 5 years old)
            $certs = $preAppData['formal_learning'] ?? [];
            $currentYear = (int) date('Y');
            foreach ($certs as $cert) {
                $year = (int) filter_var($cert['year_awarded'], FILTER_SANITIZE_NUMBER_INT);
                if ($year > 0 && ($currentYear - $year) > 5) {
                    return back()->withErrors([
                        'eligibility' => "Certificate '{$cert['title_of_certification']}' was awarded in {$year}, which is older than the allowed 5 years validity."
                    ])->withInput();
                }
            }
        }

        $selectedCourse = null;
        if ($isApelC && $request->filled('course_id')) {
            $selectedCourse = Course::where('_id', $request->course_id)->first();
        }

        $programApplied = $application->program_applied;
        if ($isApelA) {
            $programApplied = $request->program_applied ?? '';
        } elseif ($selectedCourse) {
            $programApplied = $selectedCourse->course_name . ' (' . $selectedCourse->course_code . ')';
        }

        $evidenceFiles = $application->evidence_file ?? [];
        $portfolioFiles = $application->portfolio_file ?? [];

        if ($isApelC && $request->hasFile('evidence_file')) {
            foreach ($request->file('evidence_file') as $file) {
                $evidenceFiles[] = [
                    'path' => $file->store('apel_c/evidence', 'private'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        if ($isApelC && $request->hasFile('portfolio_file')) {
            foreach ($request->file('portfolio_file') as $file) {
                $portfolioFiles[] = [
                    'path' => $file->store('apel_c/portfolio', 'private'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        $application->update([
            'application_type' => $request->application_type,
            'program_applied' => $programApplied,
            'age' => $isApelA ? $request->age : null,
            'university_name' => $isApelA ? $request->university_name : null,
            'company_name' => $isApelA ? $request->company_name : null,
            'ic_no' => $isApelA ? $request->ic_no : null,

            'credit_course_code' => $selectedCourse ? $selectedCourse->course_code : null,
            'credit_course_name' => $selectedCourse ? $selectedCourse->course_name : null,

            'highest_qualification' => $isApelA ? $request->highest_qualification : null,
            'current_job' => $isApelA ? $request->current_job : null,
            'working_experience_years' => $isApelA ? $request->working_experience_years : null,
            'working_experience_details' => $isApelA ? $request->working_experience_details : null,
            'reason_applying' => $isApelA ? $request->reason_applying : null,

            'pre_app_data' => $isApelC ? $request->pre_app_data : null,
            'self_assessment' => $isApelC ? $request->self_assessment : null,
            'target_semester' => $isApelC ? $request->target_semester : null,

            'evidence_file' => $evidenceFiles,
            'portfolio_file' => $portfolioFiles,
        ]);

        if (!$isDraft) {
            $application = $this->submitForReview($application);
        }

        $msg = $isDraft
            ? 'Application draft updated successfully.'
            : $this->submissionMessage($application);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'application_id' => (string) $application->_id
            ]);
        }
        return redirect()->route('student.applications.index')->with('success', $msg);
    }

    /**
     * Move a freshly completed application into the faculty's hands.
     *
     * APEL A goes straight to the fee. APEL C cannot — the candidate must be
     * recommended by an academic advisor first, and only then does a fee become
     * payable. The old code set payment_status to 'pending' at creation for
     * both, which is why APEL C candidates were asked to pay before anyone had
     * looked at their pre-application.
     */
    private function submitForReview(Application $application): Application
    {
        $application = StageMachine::transition(
            $application,
            ApelStage::SUBMITTED,
            ['submission_date' => $application->submission_date ?? now()],
            'Submitted by the candidate.',
        );

        $application = $application->isApelC()
            ? StageMachine::transition($application, ApelStage::ADVISOR_REVIEW, [], 'Queued for academic advisor review.')
            : StageMachine::transition($application, ApelStage::PAYMENT_DUE, [], 'Processing fee opened.');

        $this->sendMail(
            $application->user_id,
            "UTM {$application->application_type} Application Received",
            "Your {$application->application_type} application has been received.\n\n" .
                "Reference: {$application->reference()}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Stage: {$application->stageLabel()}\n\n" .
                $application->stageExplanation() . "\n\n" .
                "Thank you.\nFaculty of Computing, UTM"
        );

        return $application;
    }

    /** Tell the student what actually happens next, not just that it saved. */
    private function submissionMessage(Application $application): string
    {
        return $application->isApelC()
            ? 'Pre-application submitted. An academic advisor will review it and decide whether to recommend you for assessment — you will be told before any fee is payable.'
            : 'Application submitted. The processing fee is now payable; upload your receipt to continue.';
    }

    public function submitPayment(Request $request, $id)
    {
        $request->validate([
            'payment_receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'payment_remarks' => 'nullable|string|max:1000',
        ]);

        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        // The machine already refuses a receipt from any stage but these two;
        // catching it here lets us explain rather than show an error page.
        if (! StageMachine::can($application, ApelStage::PAYMENT_SUBMITTED)) {
            return redirect()->back()->with(
                'error',
                $application->stage() === ApelStage::PAYMENT_SUBMITTED
                    ? 'Your receipt is already with the academic office for verification.'
                    : 'No payment is due on this application at the moment.'
            );
        }

        $receiptPath = $request->file('payment_receipt')->store('payment_receipts', 'private');

        $application = StageMachine::transition(
            $application,
            ApelStage::PAYMENT_SUBMITTED,
            [
                'payment_receipt' => $receiptPath,
                'payment_remarks' => $request->payment_remarks,
            ],
            'Receipt uploaded by the candidate.',
        );

        $this->sendMail(
            Auth::id(),
            'UTM APEL Payment Receipt Received',
            "Your payment receipt has been received.\n\n" .
                "Reference: {$application->reference()}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Stage: {$application->stageLabel()}\n\n" .
                $application->stageExplanation()
        );

        return redirect()->back()
            ->with('success', 'Receipt received. The academic office will verify it and your evaluator will be assigned once it clears.');
    }

    public function submitAppeal(Request $request, $id)
    {
        $request->validate([
            'appeal_remarks' => 'required|string|max:2000',
        ]);

        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        /*
         | This guard did not exist. submitAppeal() checked nothing at all, so a
         | student could appeal a draft — or an application still being graded —
         | and the write overwrote status with 'Appeal Submitted', destroying the
         | real stage. An appeal is only meaningful against a decision.
         */
        if (! StageMachine::can($application, ApelStage::APPEAL_SUBMITTED)) {
            return redirect()->back()->with(
                'error',
                'You can only appeal once a decision has been made on this application.'
            );
        }

        $application = StageMachine::transition(
            $application,
            ApelStage::APPEAL_SUBMITTED,
            [
                'appeal_status' => 'submitted',
                'appeal_submitted_at' => now(),
                'appeal_remarks' => $request->appeal_remarks,
            ],
            'Appeal submitted by the candidate.',
        );

        $this->sendMail(
            Auth::id(),
            'UTM APEL Appeal Received',
            "Your appeal has been received.\n\n" .
                "Reference: {$application->reference()}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Your grounds: {$request->appeal_remarks}\n\n" .
                $application->stageExplanation()
        );

        return redirect()->back()
            ->with('success', 'Appeal submitted. The academic office will review it and respond.');
    }

    private function sendMail($userId, $subject, $body)
    {
        $user = User::where('_id', (string) $userId)->first();

        if (!$user || !$user->email) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new GenericQueueMail($subject, $body));
        } catch (\Exception $e) {
            Log::error('Student mail error: ' . $e->getMessage());
        }
    }

    public function printPortfolio($id)
    {
        $application = Application::where('_id', $id)->firstOrFail();
        
        $user = Auth::user();
        $isStudent = (string) $user->_id === (string) $application->user_id;
        $isEvaluator = (string) $user->_id === (string) $application->evaluator_id || (string) $user->_id === (string) ($application->evaluator_2_id ?? '');
        $isAdmin = $user->role === 'admin';

        if (!$isStudent && !$isEvaluator && !$isAdmin) {
            abort(403, 'Unauthorized access to this portfolio.');
        }

        $student = User::where('_id', (string) $application->user_id)->firstOrFail();
        
        $evaluator = null;
        if ($application->evaluator_id) {
            $evaluator = User::where('_id', (string) $application->evaluator_id)->first();
        }

        $evaluator2 = null;
        if ($application->evaluator_2_id) {
            $evaluator2 = User::where('_id', (string) $application->evaluator_2_id)->first();
        }

        return view('student.applications.print', compact('application', 'student', 'evaluator', 'evaluator2'));
    }

    public function uploadPortfolio(Request $request, $id)
    {
        $request->validate([
            'portfolio_file.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        $portfolioFiles = $application->portfolio_file ?? [];

        if ($request->hasFile('portfolio_file')) {
            foreach ($request->file('portfolio_file') as $file) {
                $portfolioFiles[] = [
                    'path' => $file->store('apel_c/portfolio', 'private'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        $application->update([
            'portfolio_file' => $portfolioFiles,
        ]);

        // If in portfolio mode, make sure AssessmentSubmission points to it
        if (($application->assessment_type ?? '') === 'portfolio') {
            \App\Models\AssessmentSubmission::updateOrCreate(
                ['application_id' => (string) $application->_id],
                [
                    'student_id' => (string) $application->user_id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'answer_file' => null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Portfolio file(s) uploaded successfully.');
    }

    public function submitPortfolio(Request $request, $id)
    {
        $application = Application::where('_id', $id)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        $request->validate([
            'portfolio_essays.school_name' => 'required|string|max:255',
            'portfolio_essays.cohort' => 'required|string|max:100',
            'portfolio_essays.essay1' => 'required|string',
            'portfolio_essays.essay2' => 'required|string',
            'portfolio_essays.essay3' => 'required|string',
            'portfolio_essays.essay4' => 'required|string',
            'cv_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'certs_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
            'samples_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        $portfolioFiles = $application->portfolio_file ?? [];

        if ($request->hasFile('cv_file')) {
            $portfolioFiles[] = [
                'path' => $request->file('cv_file')->store('apel_c/portfolio', 'private'),
                'name' => 'CV_Resume_' . $request->file('cv_file')->getClientOriginalName(),
            ];
        }
        if ($request->hasFile('certs_file')) {
            $portfolioFiles[] = [
                'path' => $request->file('certs_file')->store('apel_c/portfolio', 'private'),
                'name' => 'Certs_' . $request->file('certs_file')->getClientOriginalName(),
            ];
        }
        if ($request->hasFile('samples_file')) {
            $portfolioFiles[] = [
                'path' => $request->file('samples_file')->store('apel_c/portfolio', 'private'),
                'name' => 'WorkSamples_' . $request->file('samples_file')->getClientOriginalName(),
            ];
        }

        if (! StageMachine::can($application, ApelStage::SUBMITTED_FOR_GRADING)) {
            return redirect()->back()->with(
                'error',
                'This portfolio cannot be submitted at the moment — your assessment is not open.'
            );
        }

        \App\Models\AssessmentSubmission::updateOrCreate(
            ['application_id' => (string) $application->_id],
            [
                'student_id' => (string) $application->user_id,
                'status' => 'submitted',
                'submitted_at' => now(),
                'answer_file' => null,
            ]
        );

        $application = StageMachine::transition(
            $application,
            ApelStage::SUBMITTED_FOR_GRADING,
            [
                'portfolio_essays' => $request->portfolio_essays,
                'portfolio_file' => $portfolioFiles,
            ],
            'Portfolio submitted by the candidate.',
        );

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Portfolio Received',
            "Your portfolio essays and supporting documents have been received.\n\n" .
                "Reference: {$application->reference()}\n" .
                "Course: {$application->program_applied}\n\n" .
                $application->stageExplanation() . "\n\n" .
                "Thank you.\nFaculty of Computing, UTM"
        );

        $this->sendMail(
            $application->evaluator_id,
            'UTM APEL C Portfolio Ready for Grading',
            "A candidate has submitted their portfolio for grading.\n\n" .
                "Reference: {$application->reference()}\n" .
                "Course: {$application->program_applied}\n\n" .
                "Please sign in to the APEL Management System to grade it."
        );

        return redirect()->back()->with('success', 'Portfolio submitted. It is now with your evaluator for grading.');
    }
}
