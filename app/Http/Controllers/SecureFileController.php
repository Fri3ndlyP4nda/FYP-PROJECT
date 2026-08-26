<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way to read an uploaded file.
 *
 * Uploads previously went to the public disk and were linked directly with
 * asset('storage/'.$path). The controllers were careful — a student could only
 * open their own application, an evaluator only the ones assigned to them — but
 * none of that applied to the document itself. Anyone with the URL could read
 * another candidate's payment receipt, portfolio or graded answer script, and
 * URLs leak through Referer headers, shared screenshots and browser history.
 *
 * Files are now on a private disk with no public URL. Every read comes through
 * here, where the same ownership rules that guard the page are applied again to
 * the file, keyed on the application the file belongs to.
 */
class SecureFileController extends Controller
{
    /**
     * Files hanging off an application: receipts, evidence, portfolio.
     *
     * The path travels as a query parameter rather than a URL segment because
     * stored paths contain slashes, which route parameters escape.
     */
    public function application(Request $request, string $applicationId): StreamedResponse
    {
        $application = Application::where('_id', $applicationId)->firstOrFail();
        $path = (string) $request->query('path', '');

        abort_unless($this->mayReadApplication($application), 403, 'This document does not belong to you.');
        abort_unless($this->belongsToApplication($application, $path), 404);

        return $this->stream($path);
    }

    /**
     * Build the URL a view should link to. Kept here so no template has to
     * remember the query-parameter shape — and so nothing reaches for
     * asset('storage/...') again out of habit.
     */
    public static function url(Application $application, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return route('files.application', ['application' => (string) $application->_id, 'path' => $path]);
    }

    /** An assessment paper an evaluator published for one application. */
    public function paper(Request $request, string $paperId): StreamedResponse
    {
        $paper = AssessmentPaper::where('_id', $paperId)->firstOrFail();
        $application = Application::where('_id', (string) $paper->application_id)->firstOrFail();

        // The candidate may read the paper only once it is genuinely theirs to
        // sit — an inactive or superseded paper stays with staff.
        $isCandidate = (string) $application->user_id === (string) Auth::id();
        abort_unless(
            $this->mayReadApplication($application) && (! $isCandidate || $paper->status === 'active'),
            403,
            'This assessment paper is not available to you.',
        );

        abort_unless(! empty($paper->question_file), 404);

        return $this->stream($paper->question_file);
    }

    /** A candidate's submitted answer script. */
    public function submission(Request $request, string $submissionId): StreamedResponse
    {
        $submission = AssessmentSubmission::where('_id', $submissionId)->firstOrFail();
        $application = Application::where('_id', (string) $submission->application_id)->firstOrFail();

        abort_unless($this->mayReadApplication($application), 403, 'This submission does not belong to you.');
        abort_unless(! empty($submission->answer_file), 404);

        return $this->stream($submission->answer_file);
    }

    /**
     * The single authorisation rule, mirroring the controllers: the candidate,
     * either assigned evaluator, or an administrator.
     */
    private function mayReadApplication(Application $application): bool
    {
        $viewer = Auth::user();

        if (! $viewer) {
            return false;
        }

        if ($viewer->role === 'admin') {
            return true;
        }

        $id = (string) $viewer->_id;

        return match ($viewer->role) {
            'student' => (string) $application->user_id === $id,
            'evaluator' => (string) $application->evaluator_id === $id
                || (string) ($application->evaluator_2_id ?? '') === $id,
            default => false,
        };
    }

    /**
     * Confirm the requested path is genuinely one of this application's files.
     * Without this, an authorised viewer could pass any path they liked and
     * read a document belonging to a different application.
     */
    private function belongsToApplication(Application $application, string $path): bool
    {
        $known = [];

        if ($application->payment_receipt) {
            $known[] = $application->payment_receipt;
        }

        foreach ([$application->evidence_file, $application->portfolio_file, $application->supporting_docs] as $group) {
            foreach ((array) ($group ?? []) as $file) {
                $candidate = is_array($file) ? ($file['path'] ?? null) : $file;
                if (is_string($candidate) && $candidate !== '') {
                    $known[] = $candidate;
                }
            }
        }

        return in_array($path, $known, true);
    }

    private function stream(string $path): StreamedResponse
    {
        $disk = Storage::disk('private');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, basename($path), [
            // These documents are personal records; keep them out of shared caches.
            'Cache-Control' => 'private, max-age=0, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
