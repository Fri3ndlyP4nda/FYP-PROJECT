<?php

namespace App\Http\Controllers\Evaluator;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
use App\Http\Controllers\Controller;
use App\Mail\GenericQueueMail;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AssessmentPaperController extends Controller
{
    public function index()
    {
        $papers = AssessmentPaper::where('evaluator_id', (string) Auth::id())
            ->whereNull('parent_paper_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('question_file');

        return view('evaluator.assessments.papers.index', compact('papers'));
    }

    public function create($applicationId)
    {
        $application = Application::where('_id', $applicationId)
            ->where(function ($query) {
                $query->where('evaluator_id', (string) Auth::id())
                    ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->firstOrFail();

        $libraryPapers = AssessmentPaper::where('evaluator_id', (string) Auth::id())
            ->where('status', 'active')
            ->whereNull('parent_paper_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('question_file');

        return view('evaluator.assessments.papers.create', compact('application', 'libraryPapers'));
    }

    public function store(Request $request, $applicationId)
    {
        $application = Application::where('_id', $applicationId)
            ->where(function ($query) {
                $query->where('evaluator_id', (string) Auth::id())
                    ->orWhere('evaluator_2_id', (string) Auth::id());
            })
            ->firstOrFail();

        $request->validate([
            'paper_source' => 'required|in:library,upload',
            'library_paper_id' => 'required_if:paper_source,library|nullable|string',
            'title' => 'required_if:paper_source,upload|nullable|string|max:255',
            'instructions' => 'nullable|string',
            'question_file' => 'required_if:paper_source,upload|nullable|file|mimes:pdf|max:10240',
            'submission_deadline' => 'required|date|after:now',
        ]);

        if ($request->paper_source === 'library') {
            $libraryPaper = AssessmentPaper::where('_id', $request->library_paper_id)
                ->where('evaluator_id', (string) Auth::id())
                ->firstOrFail();

            AssessmentPaper::create([
                'application_id' => (string) $application->_id,
                'evaluator_id' => (string) Auth::id(),
                'title' => $libraryPaper->title,
                'instructions' => $libraryPaper->instructions,
                'question_file' => $libraryPaper->question_file,
                'status' => 'active',
                'parent_paper_id' => (string) $libraryPaper->_id,
                'submission_deadline' => $request->submission_deadline,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paperTitle = $libraryPaper->title;
        } else {
            $filePath = $request->file('question_file')->store('assessment_papers', 'private');

            AssessmentPaper::create([
                'application_id' => (string) $application->_id,
                'evaluator_id' => (string) Auth::id(),
                'title' => $request->title,
                'instructions' => $request->instructions,
                'question_file' => $filePath,
                'status' => 'active',
                'submission_deadline' => $request->submission_deadline,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paperTitle = $request->title;
        }

        /*
         | Publishing the paper is what makes it the candidate's turn. The old
         | code wrote 'Assessment In Progress', which reads as though staff are
         | working on it — the candidate had no way to tell that the system was
         | in fact waiting on them.
         */
        $application = StageMachine::transition(
            $application,
            ApelStage::ASSESSMENT_SET,
            [],
            "Assessment paper \"{$paperTitle}\" published by ".Auth::user()->name.'.',
        );

        $deadline = $application->stage_entered_at
            ? Carbon::parse($request->submission_deadline)->format('j F Y, g:ia')
            : null;

        $this->sendMail(
            $application->user_id,
            'UTM APEL C Assessment Ready',
            "Your assessment paper has been published and it is now your turn to act.\n\n".
                "Reference: {$application->reference()}\n".
                "Course: {$application->program_applied}\n".
                "Assessment: {$paperTitle}\n".
                ($deadline ? "Submit by: {$deadline}\n" : '').
                "\nSign in to the APEL Management System to download the paper and upload your answer."
        );

        return redirect()->route('evaluator.assessment.papers.index')
            ->with('success', 'Assessment paper assigned successfully.');
    }

    public function destroy($id)
    {
        $paper = AssessmentPaper::where('_id', $id)
            ->where('evaluator_id', (string) Auth::id())
            ->firstOrFail();

        // Check if there are other templates/clones using this exact file
        $otherReferences = AssessmentPaper::where('question_file', $paper->question_file)
            ->where('_id', '!=', $paper->_id)
            ->exists();

        if ($paper->question_file && ! $otherReferences) {
            Storage::disk('private')->delete($paper->question_file);
        }

        $paper->delete();

        return redirect()->route('evaluator.assessment.papers.index')
            ->with('success', 'Assessment paper deleted successfully from the library.');
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
            Log::error('Assessment paper mail error: '.$e->getMessage());
        }
    }
}
