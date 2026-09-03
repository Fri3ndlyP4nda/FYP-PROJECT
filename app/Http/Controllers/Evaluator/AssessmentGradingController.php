<?php

namespace App\Http\Controllers\Evaluator;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
use App\Http\Controllers\Controller;
use App\Mail\GenericQueueMail;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AssessmentGradingController extends Controller
{
    public function index()
    {
        $evaluatorId = (string) Auth::id();

        $assignedApplicationIds = Application::where('application_type', 'APEL C')
            ->where(function ($query) use ($evaluatorId) {
                $query->where('evaluator_id', $evaluatorId)
                    ->orWhere('evaluator_2_id', $evaluatorId);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $paperApplicationIds = AssessmentPaper::where('evaluator_id', $evaluatorId)
            ->pluck('application_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $applicationIds = array_values(array_unique(array_merge(
            $assignedApplicationIds,
            $paperApplicationIds
        )));

        $submissions = empty($applicationIds)
            ? collect()
            : AssessmentSubmission::whereIn('application_id', $applicationIds)
                ->whereIn('status', ['submitted', 'graded'])
                ->whereNotNull('answer_file')
                ->orderBy('submitted_at', 'desc')
                ->get();

        /*
         | Resolved here so the view is not issuing a query per row to find out
         | whose work it is looking at, and so "needs grading" is decided once.
         */
        $applications = empty($applicationIds)
            ? collect()
            : Application::whereIn('_id', $applicationIds)->get()->keyBy(fn ($a) => (string) $a->_id);

        $students = User::whereIn('_id', $submissions->pluck('student_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy(fn ($u) => (string) $u->_id);

        return view('evaluator.assessments.grading.index', [
            'submissions' => $submissions,
            'applications' => $applications,
            'students' => $students,
            'awaiting' => $submissions->filter(fn ($s) => $s->graded_at === null)->values(),
            'graded' => $submissions->filter(fn ($s) => $s->graded_at !== null)->values(),
        ]);
    }

    public function show($id)
    {
        $submission = AssessmentSubmission::where('_id', $id)->firstOrFail();

        $this->authorizeSubmissionAccess($submission);

        $application = Application::where('_id', $submission->application_id)->first();
        $isPortfolio = $application && ($application->assessment_type ?? '') === 'portfolio';

        if (empty($submission->answer_file) && ! $isPortfolio) {
            abort(404, 'No submitted answer file found for this assessment.');
        }

        return view('evaluator.assessments.grading.show', compact('submission', 'application'));
    }

    public function grade(Request $request, $id)
    {
        $submission = AssessmentSubmission::where('_id', $id)->firstOrFail();

        $application = Application::where('_id', $submission->application_id)->firstOrFail();
        $this->authorizeSubmissionAccess($submission);

        if (($application->assessment_type ?? '') !== 'portfolio' && empty($submission->answer_file)) {
            return redirect()->route('evaluator.assessment.grading.index')
                ->with('error', 'This submission has no uploaded answer file.');
        }
        $isEvaluator1 = (string) $application->evaluator_id === (string) Auth::id();
        $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();

        // Belt and braces behind authorizeSubmissionAccess: neither branch below
        // has anything to write for someone who is neither evaluator, and the
        // old code fell through such a request into an empty update that still
        // emailed the candidate a grade.
        abort_unless($isEvaluator1 || $isEvaluator2, 403, 'You are not assigned to this application.');

        if ($isEvaluator1 && ! empty($submission->evaluator_1_graded_at)) {
            return redirect()->back()->with('error', 'You have already graded this submission.');
        }

        if ($isEvaluator2 && ! empty($submission->evaluator_2_graded_at)) {
            return redirect()->back()->with('error', 'You have already graded this submission.');
        }

        $request->validate([
            'clo1' => 'required|integer|min:0|max:10',
            'clo2' => 'required|integer|min:0|max:10',
            'clo3' => 'required|integer|min:0|max:10',
            'clo4' => 'required|integer|min:0|max:10',
            'grader_feedback' => 'nullable|string|max:1000',
        ]);

        $clo1 = (int) $request->clo1;
        $clo2 = (int) $request->clo2;
        $clo3 = (int) $request->clo3;
        $clo4 = (int) $request->clo4;

        $totalCloScore = $clo1 + $clo2 + $clo3 + $clo4;
        $score = ($totalCloScore / 40) * 100;

        // PASS is equal to an achievement of at least 50% (5/10) of each Course Learning Outcome
        $result = ($clo1 >= 5 && $clo2 >= 5 && $clo3 >= 5 && $clo4 >= 5) ? 'pass' : 'fail';

        $isSingleEvaluator = empty($application->evaluator_2_id);

        $updateData = [];
        if ($isSingleEvaluator) {
            $updateData = [
                'evaluator_1_score' => $score,
                'evaluator_1_result' => $result,
                'evaluator_1_feedback' => $request->grader_feedback,
                'evaluator_1_graded_at' => now(),
                'evaluator_1_clo1' => $clo1,
                'evaluator_1_clo2' => $clo2,
                'evaluator_1_clo3' => $clo3,
                'evaluator_1_clo4' => $clo4,
                'score' => $score,
                'result' => $result,
                'graded_by' => (string) Auth::id(),
                'graded_at' => now(),
                'status' => 'graded',
            ];
        } else {
            if ($isEvaluator1) {
                $updateData = [
                    'evaluator_1_score' => $score,
                    'evaluator_1_result' => $result,
                    'evaluator_1_feedback' => $request->grader_feedback,
                    'evaluator_1_graded_at' => now(),
                    'evaluator_1_clo1' => $clo1,
                    'evaluator_1_clo2' => $clo2,
                    'evaluator_1_clo3' => $clo3,
                    'evaluator_1_clo4' => $clo4,
                ];
            } elseif ($isEvaluator2) {
                $updateData = [
                    'evaluator_2_score' => $score,
                    'evaluator_2_result' => $result,
                    'evaluator_2_feedback' => $request->grader_feedback,
                    'evaluator_2_graded_at' => now(),
                    'evaluator_2_clo1' => $clo1,
                    'evaluator_2_clo2' => $clo2,
                    'evaluator_2_clo3' => $clo3,
                    'evaluator_2_clo4' => $clo4,
                ];
            }
        }

        $submission->update($updateData);
        $submission->refresh();

        $bothGraded = ! $isSingleEvaluator && ! empty($submission->evaluator_1_graded_at) && ! empty($submission->evaluator_2_graded_at);

        if ($isSingleEvaluator || $bothGraded) {
            if ($bothGraded) {
                $bothPass = $submission->evaluator_1_result === 'pass' && $submission->evaluator_2_result === 'pass';
                $submission->update([
                    'score' => ($submission->evaluator_1_score + $submission->evaluator_2_score) / 2,
                    'result' => $bothPass ? 'pass' : 'fail',
                    'graded_at' => now(),
                    'status' => 'graded',
                ]);
            }

            $evaluatorFeedback = '';
            if ($isSingleEvaluator) {
                $evaluatorFeedback = $submission->evaluator_1_feedback;
            } else {
                $evaluatorFeedback = "Evaluator 1 Feedback: {$submission->evaluator_1_feedback}\nEvaluator 2 Feedback: {$submission->evaluator_2_feedback}";
            }

            $application = StageMachine::transition(
                $application,
                ApelStage::AWAITING_DECISION,
                [
                    'reviewed_at' => now(),
                    'evaluator_feedback' => $evaluatorFeedback,
                ],
                'Grading complete.',
            );
        } elseif (StageMachine::can($application, ApelStage::PARTIALLY_REVIEWED)) {
            $application = StageMachine::transition(
                $application,
                ApelStage::PARTIALLY_REVIEWED,
                [],
                Auth::user()->name.' graded this submission; awaiting the second evaluator.',
            );
        }

        $studentName = User::where('_id', $application->user_id)->value('name') ?? 'Student';
        ActivityLog::create([
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->name,
            'user_role' => Auth::user()->role,
            'action' => 'Graded Assessment',
            'description' => "Evaluated APEL C assessment for course '{$application->program_applied}' with score {$score}% (Result: ".ucfirst($result).") (Student: {$studentName})",
            'ip_address' => $request->ip(),
        ]);

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Assessment Graded',
            "Your APEL C assessment has been graded.\n\n".
                "Course: {$application->program_applied}\n".
                "Score: {$score}\n".
                'Result: '.ucfirst($result)."\n".
                "Status: {$application->status}\n\n".
                'Feedback: '.($request->grader_feedback ?? 'No feedback provided.')
        );

        if (($application->assessment_type ?? '') === 'portfolio') {
            return redirect()->route('evaluator.applications.show', $application->_id)
                ->with('success', 'Portfolio evaluation submitted successfully.');
        }

        return redirect()->route('evaluator.assessment.grading.index')
            ->with('success', 'Submission graded successfully.');
    }

    /**
     * Grading rights follow the *assignment*, never the paper.
     *
     * This used to pass on `$ownsApplication || $ownsPaper`, where $ownsPaper
     * matched only AssessmentPaper.evaluator_id. Once an admin reassigned an
     * application, the previous evaluator still owned the paper row and kept
     * full grading access to a candidate who was no longer theirs — and because
     * grade() computed $isEvaluator1/$isEvaluator2 *after* this check, that
     * evaluator either graded a single-evaluator case outright or, on a
     * two-evaluator case, wrote an empty update, reset the application, and
     * still triggered the "your assessment has been graded" email for a grade
     * that was never saved.
     */
    private function authorizeSubmissionAccess(AssessmentSubmission $submission): void
    {
        $evaluatorId = (string) Auth::id();

        $isAssigned = Application::where('_id', $submission->application_id)
            ->where('application_type', 'APEL C')
            ->where(function ($query) use ($evaluatorId) {
                $query->where('evaluator_id', $evaluatorId)
                    ->orWhere('evaluator_2_id', $evaluatorId);
            })
            ->exists();

        abort_unless($isAssigned, 404);
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
            Log::error('Assessment grading mail error: '.$e->getMessage());
        }
    }
}
