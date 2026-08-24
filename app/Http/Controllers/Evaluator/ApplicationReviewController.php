<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\GenericQueueMail;

class ApplicationReviewController extends Controller
{
    public function index()
    {
        $applications = Application::where('status', '!=', 'Draft')
            ->where(function($query) {
                $query->where('evaluator_id', (string) Auth::id())
                      ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->orderBy('submission_date', 'desc')
            ->get();

        return view('evaluator.applications.index', compact('applications'));
    }

    public function show($id)
    {
        $application = Application::where('_id', $id)
            ->where(function($query) {
                $query->where('evaluator_id', (string) Auth::id())
                      ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->firstOrFail();

        if ($application->status === 'Assessor Assigned' || $application->status === 'Evaluator Assigned') {
            $application->update([
                'status' => 'Assessment In Progress',
                'status_updated_at' => now(),
            ]);

            $application = Application::where('_id', $id)->firstOrFail();

            $this->sendMail(
                $application->user_id,
                'UTM APEL Assessment In Progress',
                "Your application is now being assessed by the assigned evaluator.\n\n" .
                    "Application: {$application->application_type}\n" .
                    "Programme / Course: {$application->program_applied}\n" .
                    "Status: Assessment In Progress"
            );
        }

        if ($application->application_type === 'APEL C' && ($application->assessment_type ?? '') === 'portfolio') {
            \App\Models\AssessmentSubmission::firstOrCreate(
                ['application_id' => (string) $application->_id],
                [
                    'student_id' => (string) $application->user_id,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'answer_file' => null,
                ]
            );
        }

        return view('evaluator.applications.show', compact('application'));
    }

    public function update(Request $request, $id)
    {
        $application = Application::where('_id', $id)
            ->where(function($query) {
                $query->where('evaluator_id', (string) Auth::id())
                      ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->firstOrFail();

        if ($application->application_type === 'APEL A') {
            $isEvaluator1 = (string) $application->evaluator_id === (string) Auth::id();
            $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();

            if ($isEvaluator1 && !empty($application->evaluator_1_reviewed_at)) {
                return redirect()->back()->with('error', 'You have already reviewed this application.');
            }
            if ($isEvaluator2 && !empty($application->evaluator_2_reviewed_at)) {
                return redirect()->back()->with('error', 'You have already reviewed this application.');
            }

            $request->validate([
                'admission_decision' => 'required|in:pending,recommended,not_recommended',
                'evaluator_feedback' => 'nullable|string|max:1000',
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
            $bothReviewed = !empty($application->evaluator_1_reviewed_at) && !empty($application->evaluator_2_reviewed_at);

            if ($isSingleEvaluator || $bothReviewed) {
                if ($isSingleEvaluator) {
                    $finalRec = $application->evaluator_1_decision;
                    $feedback = $application->evaluator_1_feedback;
                } else {
                    $bothPass = $application->evaluator_1_decision === 'recommended' && $application->evaluator_2_decision === 'recommended';
                    $finalRec = $bothPass ? 'recommended' : 'not_recommended';
                    $feedback = "Evaluator 1 Feedback: {$application->evaluator_1_feedback}\nEvaluator 2 Feedback: {$application->evaluator_2_feedback}";
                }

                $application->update([
                    'admission_decision' => $finalRec,
                    'evaluator_feedback' => $feedback,
                    'reviewed_at' => now(),
                    'review_stage' => 'decision_completed',
                    'status' => 'Awaiting Final Decision',
                    'status_updated_at' => now(),
                ]);
            } else {
                $application->update([
                    'status' => 'Assessment In Progress',
                    'status_updated_at' => now(),
                ]);
            }

            $application->refresh();

            $this->sendMail(
                $application->user_id,
                'UTM APEL A Evaluator Review Updated',
                "Your APEL A evaluator review has been updated.\n\n" .
                    "Programme: {$application->program_applied}\n" .
                    "Evaluator Recommendation: " . ucfirst(str_replace('_', ' ', $application->admission_decision ?? 'pending')) . "\n" .
                    "Status: {$application->status}\n\n" .
                    "Feedback: " . ($application->evaluator_feedback ?? 'No feedback provided.')
            );

            return redirect()->route('evaluator.applications.index')
                ->with('success', 'APEL A application updated successfully.');
        }

        if ($application->application_type === 'APEL C') {
            $hasPaper = \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)->exists();
            $hasGraded = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->where('status', 'graded')->exists();

            if (!$hasPaper || !$hasGraded) {
                return redirect()->back()
                    ->withErrors(['error' => 'You must upload the assessment paper and grade the student\'s submission before updating the application.'])
                    ->withInput();
            }
        }

        $request->validate([
            'status' => 'required|in:Assessment In Progress,Awaiting Final Decision',
            'evaluator_feedback' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status' => $request->status,
            'evaluator_feedback' => $request->evaluator_feedback,
            'reviewed_at' => now(),
            'status_updated_at' => now(),
        ]);

        $application = Application::where('_id', $id)->firstOrFail();

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Evaluator Review Updated',
            "Your APEL C evaluator review has been updated.\n\n" .
                "Course: {$application->program_applied}\n" .
                "Status: {$application->status}\n\n" .
                "Feedback: " . ($application->evaluator_feedback ?? 'No feedback provided.')
        );

        return redirect()->route('evaluator.applications.index')
            ->with('success', 'Application updated successfully.');
    }

    public function apelAIndex()
    {
        $applications = Application::where('application_type', 'APEL A')
            ->where(function($query) {
                $query->where('evaluator_id', (string) Auth::id())
                      ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->orderBy('submission_date', 'desc')
            ->get();

        return view('evaluator.apel_a.index', compact('applications'));
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
            Log::error('Evaluator review mail error: ' . $e->getMessage());
        }
    }
}
