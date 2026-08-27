<?php

namespace App\Http\Controllers\Student;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
use App\Http\Controllers\Controller;
use App\Mail\GenericQueueMail;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AssessmentSubmissionController extends Controller
{
    public function show($applicationId)
    {
        $application = Application::where('_id', $applicationId)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        $paper = AssessmentPaper::where('application_id', (string) $application->_id)
            ->where('status', 'active')
            ->first();

        if (! $paper) {
            abort(404, 'No assessment paper available yet.');
        }

        $submission = AssessmentSubmission::where('application_id', (string) $application->_id)
            ->where('student_id', (string) Auth::id())
            ->first();

        return view('student.assessments.show', compact('application', 'paper', 'submission'));
    }

    public function submit(Request $request, $applicationId)
    {
        $application = Application::where('_id', $applicationId)
            ->where('user_id', (string) Auth::id())
            ->firstOrFail();

        $paper = AssessmentPaper::where('application_id', (string) $application->_id)
            ->where('status', 'active')
            ->firstOrFail();

        if ($paper->submission_deadline && Carbon::parse($paper->submission_deadline)->isPast()) {
            return redirect()->route('student.assessment.show', $application->_id)
                ->withErrors(['error' => 'The submission deadline has passed and you can no longer submit your assessment.']);
        }

        $existing = AssessmentSubmission::where('application_id', (string) $application->_id)
            ->where('student_id', (string) Auth::id())
            ->first();

        if ($existing && ! empty($existing->answer_file)) {
            return redirect()->route('student.assessment.show', $application->_id)
                ->with('success', 'Assessment already submitted.');
        }

        $request->validate([
            'answer_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if (! StageMachine::can($application, ApelStage::SUBMITTED_FOR_GRADING)) {
            return redirect()->route('student.assessment.show', $application->_id)
                ->with('error', 'This assessment is not open for submission at the moment.');
        }

        $answerPath = $request->file('answer_file')->store('assessment_answers', 'private');

        if ($existing) {
            $existing->update([
                'assessment_paper_id' => (string) $paper->_id,
                'answer_file' => $answerPath,
                'submitted_at' => now(),
                'status' => 'submitted',
            ]);
        } else {
            AssessmentSubmission::create([
                'assessment_paper_id' => (string) $paper->_id,
                'application_id' => (string) $application->_id,
                'student_id' => (string) Auth::id(),
                'answer_file' => $answerPath,
                'score' => null,
                'result' => 'pending',
                'grader_feedback' => null,
                'graded_by' => null,
                'submitted_at' => now(),
                'graded_at' => null,
                'status' => 'submitted',
            ]);
        }

        /*
         | This wrote status = 'Awaiting Final Decision' the instant the answer
         | landed — before a single mark existed. The admin queue therefore
         | showed ungraded work as ready to decide, and the student was told
         | their application was awaiting a decision that could not yet be made.
         |
         | An answer that has been handed in is awaiting *grading*. The machine
         | will not let it reach a decision until an evaluator has graded it.
         */
        $application = StageMachine::transition(
            $application,
            ApelStage::SUBMITTED_FOR_GRADING,
            [],
            'Answer script submitted by the candidate.',
        );

        $this->sendMail(
            Auth::id(),
            'UTM APEL C Assessment Received',
            "Your answer has been received.\n\n".
                "Reference: {$application->reference()}\n".
                "Course: {$application->program_applied}\n\n".
                $application->stageExplanation()
        );

        $this->sendMail(
            $application->evaluator_id,
            'UTM APEL C Answer Ready for Grading',
            "A candidate has submitted an answer script for grading.\n\n".
                "Reference: {$application->reference()}\n".
                "Course: {$application->program_applied}\n\n".
                'Please sign in to the APEL Management System to grade it.'
        );

        return redirect()->route('student.assessment.show', $application->_id)
            ->with('success', 'Assessment answer uploaded successfully.');
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
            Log::error('Assessment submission mail error: '.$e->getMessage());
        }
    }
}
