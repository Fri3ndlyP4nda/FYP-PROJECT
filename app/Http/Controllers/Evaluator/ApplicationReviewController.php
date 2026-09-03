<?php

namespace App\Http\Controllers\Evaluator;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
use App\Http\Controllers\Controller;
use App\Mail\GenericQueueMail;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\ApplicationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ApplicationReviewController extends Controller
{
    public function index()
    {
        $applications = Application::where('status', '!=', 'Draft')
            ->where(function ($query) {
                $query->where('evaluator_id', (string) Auth::id())
                    ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->orderBy('submission_date', 'desc')
            ->get();

        // Grouped by whose move it is, so the top of the page is the work.
        $cases = ApplicationCase::collect($applications, Auth::user());

        return view('evaluator.applications.index', [
            'cases' => $cases,
            'waitingOnMe' => ApplicationCase::awaitingViewer($cases),
            'withOthers' => ApplicationCase::elsewhere($cases),
            'closed' => ApplicationCase::closed($cases),
        ]);
    }

    public function show($id)
    {
        $application = Application::where('_id', $id)
            ->where(function ($query) {
                $query->where('evaluator_id', (string) Auth::id())
                    ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->firstOrFail();

        /*
         | Opening an APEL A application starts the review. This used to be
         | gated on two different spellings of the same state — 'Assessor
         | Assigned' and 'Evaluator Assigned' — because the two had drifted
         | apart in the codebase. There is now one stage.
         |
         | APEL C is deliberately excluded: an evaluator opening an APEL C case
         | has not started assessing it, they still have to set the assessment
         | first, and moving it here would skip that step.
         */
        if (! $application->isApelC() && StageMachine::can($application, ApelStage::UNDER_REVIEW)) {
            $application = StageMachine::transition(
                $application,
                ApelStage::UNDER_REVIEW,
                [],
                'Opened by '.Auth::user()->name.'.',
            );

            $this->sendMail(
                $application->user_id,
                'UTM APEL Review Started',
                "Your application is now being assessed.\n\n".
                    "Reference: {$application->reference()}\n".
                    "Programme / Course: {$application->program_applied}\n\n".
                    $application->stageExplanation()
            );
        }

        if ($application->application_type === 'APEL C' && ($application->assessment_type ?? '') === 'portfolio') {
            AssessmentSubmission::firstOrCreate(
                ['application_id' => (string) $application->_id],
                [
                    'student_id' => (string) $application->user_id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'answer_file' => null,
                ]
            );
        }

        return view('evaluator.applications.show', [
            'application' => $application,
            'case' => ApplicationCase::for($application, Auth::user()),
            'candidate' => User::where('_id', (string) $application->user_id)->first(),
            'paper' => AssessmentPaper::where('application_id', (string) $application->_id)
                ->where('status', 'active')
                ->first(),
            'submission' => AssessmentSubmission::where('application_id', (string) $application->_id)->first(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $application = Application::where('_id', $id)
            ->where(function ($query) {
                $query->where('evaluator_id', (string) Auth::id())
                    ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->firstOrFail();

        if ($application->application_type === 'APEL A') {
            $isEvaluator1 = (string) $application->evaluator_id === (string) Auth::id();
            $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();

            if ($isEvaluator1 && ! empty($application->evaluator_1_reviewed_at)) {
                return redirect()->back()->with('error', 'You have already reviewed this application.');
            }
            if ($isEvaluator2 && ! empty($application->evaluator_2_reviewed_at)) {
                return redirect()->back()->with('error', 'You have already reviewed this application.');
            }

            /*
             | 'pending' used to be an accepted choice here. Picking it stamped
             | evaluator_1_reviewed_at, which the guard above then treats as a
             | completed review — so the evaluator could never revise it — while
             | the application consolidated to "Awaiting Final Decision" carrying
             | a recommendation of "pending" that no one could act on. The record
             | was stranded, permanently.
             |
             | A review is a recommendation. An evaluator who is not ready to
             | give one simply does not submit the form yet.
             */
            $request->validate([
                'admission_decision' => 'required|in:recommended,not_recommended',
                'evaluator_feedback' => 'required|string|max:1000',
            ], [
                'admission_decision.in' => 'Choose whether you recommend this candidate. Leave the review unsubmitted if you are not ready to decide.',
                'evaluator_feedback.required' => 'Please record the reasoning behind your recommendation — the candidate and the faculty both rely on it.',
            ]);

            $decision = $request->admission_decision;

            $updateData = [];
            if ($isEvaluator1) {
                $updateData = [
                    'evaluator_1_decision' => $decision,
                    'evaluator_1_feedback' => $request->evaluator_feedback,
                    'evaluator_1_reviewed_at' => now(),
                ];
            } else {
                $updateData = [
                    'evaluator_2_decision' => $decision,
                    'evaluator_2_reviewed_at' => now(),
                    'evaluator_2_feedback' => $request->evaluator_feedback,
                ];
            }

            $application->update($updateData);
            $application->refresh();

            // Consolidate
            $isSingleEvaluator = empty($application->evaluator_2_id);
            $bothReviewed = ! empty($application->evaluator_1_reviewed_at) && ! empty($application->evaluator_2_reviewed_at);

            if ($isSingleEvaluator || $bothReviewed) {
                $split = false;

                if ($isSingleEvaluator) {
                    $finalRec = $application->evaluator_1_decision;
                    $feedback = $application->evaluator_1_feedback;
                } else {
                    $first = $application->evaluator_1_decision;
                    $second = $application->evaluator_2_decision;
                    $split = $first !== $second;

                    /*
                     | Two evaluators who disagreed used to produce an automatic
                     | 'not_recommended' — one dissenting voice silently sank the
                     | candidate, and nothing told the faculty a disagreement had
                     | even occurred. A split panel is a real outcome that needs a
                     | human to resolve it, so it is now recorded as such and put
                     | in front of the faculty with both opinions intact.
                     */
                    $finalRec = $split ? 'split' : $first;
                    $feedback = "Evaluator 1 ({$first}): {$application->evaluator_1_feedback}"
                        ."\n\nEvaluator 2 ({$second}): {$application->evaluator_2_feedback}";
                }

                $application = StageMachine::transition(
                    $application,
                    ApelStage::AWAITING_DECISION,
                    [
                        'admission_decision' => $finalRec,
                        'evaluator_feedback' => $feedback,
                        'reviewed_at' => now(),
                        'panel_split' => $split,
                    ],
                    $split
                        ? 'Both evaluators reported and disagreed — referred to the faculty to resolve.'
                        : 'All evaluator reviews received.',
                );
            } else {
                $application = StageMachine::transition(
                    $application,
                    ApelStage::PARTIALLY_REVIEWED,
                    [],
                    Auth::user()->name.' submitted their review; awaiting the second evaluator.',
                );
            }

            // The candidate is told a review landed, never what it said — the
            // recommendation is not the decision, and only the faculty makes that.
            $this->sendMail(
                $application->user_id,
                'UTM APEL A Review Progress',
                "There has been progress on your application.\n\n".
                    "Reference: {$application->reference()}\n".
                    "Programme: {$application->program_applied}\n\n".
                    $application->stageExplanation()
            );

            return redirect()->route('evaluator.applications.index')
                ->with('success', $application->stage() === ApelStage::AWAITING_DECISION
                    ? 'Review submitted. This application now goes to the faculty for the final decision.'
                    : 'Review submitted. The application is waiting on the second evaluator.');
        }

        /*
         | APEL C.
         |
         | This branch used to accept a 'status' field and write it straight to
         | the record, letting an evaluator move an application to "Awaiting
         | Final Decision" by hand. That is what grading is for — an APEL C case
         | reaches a decision because it was graded, not because someone typed a
         | status. Grading already advances the stage, so all this action does
         | now is attach the evaluator's written remarks.
         */
        $hasGraded = AssessmentSubmission::where('application_id', (string) $application->_id)
            ->whereNotNull('graded_at')
            ->exists();

        if (! $hasGraded) {
            return redirect()->back()
                ->withErrors(['error' => 'Grade the candidate\'s submission first — that is what moves this application forward.'])
                ->withInput();
        }

        $request->validate([
            'evaluator_feedback' => 'required|string|max:1000',
        ]);

        StageMachine::record($application, [
            'evaluator_feedback' => $request->evaluator_feedback,
        ]);

        return redirect()->route('evaluator.applications.index')
            ->with('success', 'Your remarks have been saved against this application.');
    }

    public function apelAIndex()
    {
        $applications = Application::where('application_type', 'APEL A')
            ->where(function ($query) {
                $query->where('evaluator_id', (string) Auth::id())
                    ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->orderBy('submission_date', 'desc')
            ->get();

        $cases = ApplicationCase::collect($applications, Auth::user());

        return view('evaluator.apel_a.index', [
            'cases' => $cases,
            'waitingOnMe' => ApplicationCase::awaitingViewer($cases),
            'withOthers' => ApplicationCase::elsewhere($cases),
            'closed' => ApplicationCase::closed($cases),
        ]);
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
            Log::error('Evaluator review mail error: '.$e->getMessage());
        }
    }
}
