<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\GenericQueueMail;

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

        if (!$paper) {
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

        if ($paper->submission_deadline && \Carbon\Carbon::parse($paper->submission_deadline)->isPast()) {
            return redirect()->route('student.assessment.show', $application->_id)
                ->withErrors(['error' => 'The submission deadline has passed and you can no longer submit your assessment.']);
        }

        $existing = AssessmentSubmission::where('application_id', (string) $application->_id)
            ->where('student_id', (string) Auth::id())
            ->first();

        if ($existing && !empty($existing->answer_file)) {
            return redirect()->route('student.assessment.show', $application->_id)
                ->with('success', 'Assessment already submitted.');
        }

        $request->validate([
            'answer_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $answerPath = $request->file('answer_file')->store('assessment_answers', 'public');

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

        Application::where('_id', $applicationId)->update([
            'credit_status' => 'submitted_for_grading',
            'status' => 'Awaiting Final Decision',
            'status_updated_at' => now(),
        ]);

        $this->sendMail(
            Auth::id(),
            'UTM APEL C Assessment Submitted',
            "Your APEL C assessment answer has been submitted successfully.\n\n" .
                "Course: {$application->program_applied}\n" .
                "Status: Submitted for Grading"
        );

        $this->sendMail(
            $application->evaluator_id,
            'UTM APEL C Assessment Answer Submitted',
            "A student has submitted an assessment answer for grading.\n\n" .
                "Course: {$application->program_applied}\n" .
                "Please log in to the APEL Management System to grade the submission."
        );

        return redirect()->route('student.assessment.show', $application->_id)
            ->with('success', 'Assessment answer uploaded successfully.');
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
            Log::error('Assessment submission mail error: ' . $e->getMessage());
        }
    }
}
