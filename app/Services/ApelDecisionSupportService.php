<?php

namespace App\Services;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\Eligibility;
use App\Models\Application;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApelDecisionSupportService
{
    public function evaluateApelA(Application $application): array
    {
        if ($application->application_type !== 'APEL A') {
            return [
                'score' => 0,
                'recommendation' => 'Not applicable',
                'summary' => 'This scorecard is only used for APEL A applications.',
                'criteria' => [],
                'evidence_gaps' => collect(),
                'review_classification' => [
                    'label' => 'Not applicable',
                    'level' => 'medium',
                    'reason' => 'This classification is only used for APEL A applications.',
                ],
            ];
        }

        $criteria = [];

        $age = (int) ($application->age ?? 0);
        $minimumAge = Eligibility::minimumAge();
        $agePass = $age >= $minimumAge;
        $criteria[] = $this->criterion(
            'Minimum age requirement',
            $agePass ? 'pass' : 'fail',
            $agePass ? 20 : 0,
            20,
            $age > 0 ? "{$age} years old" : 'Not provided',
            $agePass
                ? 'Candidate meets the minimum age requirement.'
                : "Candidate must be at least {$minimumAge} years old for APEL A.",
            true
        );

        $ic = preg_replace('/\D/', '', (string) ($application->ic_no ?? ''));
        $criteria[] = $this->criterion(
            'Malaysian IC format',
            strlen($ic) === 12 ? 'pass' : 'fail',
            strlen($ic) === 12 ? 15 : 0,
            15,
            $application->ic_no ?: 'Not provided',
            strlen($ic) === 12
                ? 'The IC number has a valid 12-digit structure.'
                : 'A valid 12-digit IC number is required for the eligibility screen.',
            true
        );

        /*
         | This was stripos($qualification, 'diploma') === 0 — a duplicate of
         | the rule in Student\ApplicationController, and wrong in the same way:
         | the text had to *begin* with "Diploma", so a Bachelor's degree scored
         | zero on the qualification criterion. Both now read the one rule in
         | App\Domain\Apel\Eligibility.
         */
        $qualification = trim((string) ($application->highest_qualification ?? ''));
        $qualificationPass = Eligibility::qualificationAccepted($qualification);
        $criteria[] = $this->criterion(
            'Highest qualification',
            $qualificationPass ? 'pass' : 'fail',
            $qualificationPass ? 20 : 0,
            20,
            $qualification !== '' ? $qualification : 'Not provided',
            $qualificationPass
                ? 'Highest qualification meets the APEL A entry floor.'
                : Eligibility::qualificationMessage($qualification),
            true
        );

        $experienceYears = (float) ($application->working_experience_years ?? 0);
        $experienceDetails = trim((string) ($application->working_experience_details ?? ''));
        $experiencePoints = 0;
        $experienceStatus = 'fail';

        if ($experienceYears >= 5 && $experienceDetails !== '') {
            $experiencePoints = 20;
            $experienceStatus = 'pass';
        } elseif ($experienceYears > 0 && $experienceDetails !== '') {
            $experiencePoints = 14;
            $experienceStatus = 'warning';
        } elseif ($experienceYears > 0 || $experienceDetails !== '') {
            $experiencePoints = 8;
            $experienceStatus = 'warning';
        }

        $criteria[] = $this->criterion(
            'Working experience readiness',
            $experienceStatus,
            $experiencePoints,
            20,
            $experienceYears > 0 ? "{$experienceYears} year(s)" : 'Not provided',
            match ($experienceStatus) {
                'pass' => 'Work experience years and explanation are strong enough for review.',
                'warning' => 'Work experience exists, but the evaluator may need clearer supporting details.',
                default => 'Work experience years and details are missing or too weak for efficient review.',
            },
            false
        );

        $reason = trim((string) ($application->reason_applying ?? ''));
        $reasonLength = strlen($reason);
        $criteria[] = $this->criterion(
            'Application rationale',
            $reasonLength >= 80 ? 'pass' : ($reasonLength > 0 ? 'warning' : 'fail'),
            $reasonLength >= 80 ? 15 : ($reasonLength > 0 ? 8 : 0),
            15,
            $reasonLength > 0 ? "{$reasonLength} characters" : 'Not provided',
            $reasonLength >= 80
                ? 'Candidate gave enough rationale for evaluator context.'
                : 'A clearer reason helps the evaluator assess academic readiness faster.',
            false
        );

        $profileFields = collect([
            $application->university_name,
            $application->company_name,
            $application->current_job,
        ])->filter(fn ($value) => trim((string) $value) !== '')->count();

        $criteria[] = $this->criterion(
            'Profile completeness',
            $profileFields >= 3 ? 'pass' : ($profileFields > 0 ? 'warning' : 'fail'),
            $profileFields >= 3 ? 10 : ($profileFields > 0 ? 5 : 0),
            10,
            "{$profileFields}/3 fields completed",
            $profileFields >= 3
                ? 'Academic and employment context is complete.'
                : 'Missing background fields may slow down manual checking.',
            false
        );

        $totalPoints = collect($criteria)->sum('points');
        $maxPoints = collect($criteria)->sum('max_points');
        $score = $maxPoints > 0 ? (int) round(($totalPoints / $maxPoints) * 100) : 0;
        $criticalFailed = collect($criteria)
            ->contains(fn ($item) => $item['critical'] && $item['status'] === 'fail');

        if ($criticalFailed) {
            $recommendation = 'Not Ready';
            $summary = 'Critical APEL A requirement failed. Admin should resolve the flagged criteria before evaluator review.';
        } elseif ($score >= 80) {
            $recommendation = 'Ready for Evaluator Review';
            $summary = 'The application passes all critical rules and has enough detail for efficient evaluator review.';
        } elseif ($score >= 60) {
            $recommendation = 'Needs Admin Review';
            $summary = 'Critical rules pass, but some supporting details are weak and should be checked before assignment.';
        } else {
            $recommendation = 'Incomplete';
            $summary = 'The application needs more supporting information before it can be reviewed efficiently.';
        }

        return [
            'score' => $score,
            'recommendation' => $recommendation,
            'summary' => $summary,
            'criteria' => $criteria,
            'evidence_gaps' => $this->evidenceGapsFromCriteria($criteria),
            'review_classification' => $this->classifyReview($score, $criteria),
        ];
    }

    public function generateEvaluatorBrief(Application $application): array
    {
        $eligibility = $this->evaluateApelA($application);
        $classification = $eligibility['review_classification'];
        $gaps = $eligibility['evidence_gaps'];
        $criticalFailures = collect($eligibility['criteria'])
            ->where('critical', true)
            ->where('status', 'fail')
            ->values();

        $focusAreas = $this->buildReviewFocus($application, $eligibility);
        $nextActions = $this->buildNextActions($classification, $gaps, $criticalFailures);

        return [
            'generated_at' => now(),
            'classification' => $classification,
            'eligibility' => $eligibility,
            'evidence_gaps' => $gaps,
            'critical_failures' => $criticalFailures,
            'focus_areas' => $focusAreas,
            'next_actions' => $nextActions,
            'efficiency_notes' => [
                'Manual screening is reduced because critical eligibility rules are checked before evaluator assignment.',
                'Evaluator can focus only on flagged evidence gaps instead of rereading every application field.',
                'Admin can use classification to separate fast-track cases from high-risk manual-review cases.',
            ],
        ];
    }

    public function rankEvaluators(): Collection
    {
        return User::where('role', 'evaluator')
            ->get()
            ->map(function (User $evaluator) {
                $evaluatorId = (string) $evaluator->_id;

                $assignedApplications = Application::where('status', '!=', 'Draft')
                    ->where(function ($query) use ($evaluatorId) {
                        $query->where('evaluator_id', $evaluatorId)
                            ->orWhere('evaluator_2_id', $evaluatorId);
                    })
                    ->get();

                $activeApplications = $assignedApplications->filter(function ($application) {
                    return ! in_array($application->status ?? '', ['Final Approved', 'Final Rejected'])
                        && ! in_array($application->final_decision ?? '', ['approved', 'rejected'])
                        && ! in_array($application->credit_decision ?? '', ['approved', 'rejected']);
                });

                $applicationIds = $assignedApplications
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->toArray();

                $pendingSubmissions = empty($applicationIds)
                    ? 0
                    : AssessmentSubmission::whereIn('application_id', $applicationIds)
                        ->where('status', 'submitted')
                        ->count();

                $completedDurations = $assignedApplications
                    ->filter(fn ($application) => $application->assigned_at && ($application->reviewed_at || $application->finalized_at || $application->credit_decided_at))
                    ->map(function ($application) {
                        $start = $this->dateValue($application->assigned_at);
                        $end = $this->dateValue($application->reviewed_at ?? $application->finalized_at ?? $application->credit_decided_at);

                        return $start && $end ? $start->diffInDays($end) : null;
                    })
                    ->filter(fn ($days) => $days !== null);

                $averageCompletionDays = $completedDurations->count() > 0
                    ? round($completedDurations->avg(), 1)
                    : null;

                $rankScore = ($activeApplications->count() * 5)
                    + ($pendingSubmissions * 3)
                    + (($averageCompletionDays ?? 3) * 1);

                return [
                    'id' => $evaluatorId,
                    'name' => $evaluator->name,
                    'active_assignments' => $activeApplications->count(),
                    'pending_submissions' => $pendingSubmissions,
                    'completed_reviews' => $assignedApplications->count() - $activeApplications->count(),
                    'average_completion_days' => $averageCompletionDays,
                    'rank_score' => round($rankScore, 1),
                    'recommendation_reason' => $activeApplications->count() === 0
                        ? 'No active workload detected.'
                        : 'Balanced against current assignments and pending submissions.',
                ];
            })
            ->sortBy('rank_score')
            ->values();
    }

    public function workflowMetrics(): array
    {
        $applications = Application::where('status', '!=', 'Draft')->get();

        $activeApplications = $applications->filter(function ($application) {
            return ! in_array($application->status ?? '', ['Final Approved', 'Final Rejected'])
                && ! in_array($application->final_decision ?? '', ['approved', 'rejected'])
                && ! in_array($application->credit_decision ?? '', ['approved', 'rejected']);
        });

        $delayedApplications = $activeApplications->filter(function ($application) {
            $lastUpdate = $this->dateValue($application->status_updated_at ?? $application->submission_date);

            return $lastUpdate && $lastUpdate->lt(now()->subDays(7));
        });

        // Read from the stage rather than payment_status, which used to be
        // written independently and could disagree with where the application
        // actually stood.
        $unassignedReady = $activeApplications->filter(
            fn ($application) => $application->stage() === ApelStage::PAYMENT_VERIFIED
        );

        $pendingPayment = $activeApplications->filter(fn ($application) => in_array(
            $application->stage(),
            [ApelStage::PAYMENT_DUE, ApelStage::PAYMENT_SUBMITTED, ApelStage::PAYMENT_REJECTED],
            true,
        ));

        $completedDurations = $applications
            ->filter(function ($application) {
                return in_array($application->status ?? '', ['Final Approved', 'Final Rejected'])
                    || in_array($application->final_decision ?? '', ['approved', 'rejected'])
                    || in_array($application->credit_decision ?? '', ['approved', 'rejected']);
            })
            ->map(function ($application) {
                $start = $this->dateValue($application->submission_date);
                $end = $this->dateValue($application->finalized_at ?? $application->credit_decided_at ?? $application->reviewed_at ?? $application->status_updated_at);

                return $start && $end ? $start->diffInDays($end) : null;
            })
            ->filter(fn ($days) => $days !== null);

        $bottlenecks = $activeApplications
            ->groupBy(fn ($application) => $application->status ?? 'Unknown')
            ->map(fn ($group, $status) => [
                'stage' => $status,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(5);

        return [
            'active_count' => $activeApplications->count(),
            'delayed_count' => $delayedApplications->count(),
            'unassigned_ready_count' => $unassignedReady->count(),
            'pending_payment_count' => $pendingPayment->count(),
            'average_processing_days' => $completedDurations->count() > 0
                ? round($completedDurations->avg(), 1)
                : 0,
            'bottlenecks' => $bottlenecks,
            'delayed_applications' => $delayedApplications->take(5)->values(),
        ];
    }

    private function evidenceGapsFromCriteria(array $criteria): Collection
    {
        return collect($criteria)
            ->filter(fn ($criterion) => in_array($criterion['status'], ['warning', 'fail']))
            ->map(fn ($criterion) => [
                'area' => $criterion['name'],
                'severity' => $criterion['critical'] && $criterion['status'] === 'fail'
                    ? 'high'
                    : ($criterion['status'] === 'fail' ? 'medium' : 'low'),
                'value' => $criterion['value'],
                'message' => $criterion['message'],
            ])
            ->values();
    }

    private function classifyReview(int $score, array $criteria): array
    {
        $criticalFailed = collect($criteria)
            ->contains(fn ($criterion) => $criterion['critical'] && $criterion['status'] === 'fail');
        $warningCount = collect($criteria)
            ->where('status', 'warning')
            ->count();

        if ($criticalFailed || $score < 60) {
            return [
                'label' => 'High Risk / Manual Review',
                'level' => 'high',
                'reason' => 'Critical rules failed or the readiness score is too low for efficient evaluator assignment.',
            ];
        }

        if ($score >= 85 && $warningCount === 0) {
            return [
                'label' => 'Fast Track',
                'level' => 'low',
                'reason' => 'All major requirements are satisfied with strong supporting information.',
            ];
        }

        return [
            'label' => 'Normal Review',
            'level' => 'medium',
            'reason' => 'Critical requirements pass, but some evidence should still be checked by the reviewer.',
        ];
    }

    private function buildReviewFocus(Application $application, array $eligibility): Collection
    {
        $focus = collect();
        $criteria = collect($eligibility['criteria'])->keyBy('name');

        if (($criteria->get('Working experience readiness')['status'] ?? null) !== 'pass') {
            $focus->push([
                'title' => 'Verify work experience relevance',
                'detail' => 'Check whether the stated job scope and experience are strongly related to the applied programme.',
            ]);
        }

        if (($criteria->get('Application rationale')['status'] ?? null) !== 'pass') {
            $focus->push([
                'title' => 'Review application rationale',
                'detail' => 'Ask whether the candidate clearly explains why APEL A admission is suitable for their background.',
            ]);
        }

        if (($criteria->get('Profile completeness')['status'] ?? null) !== 'pass') {
            $focus->push([
                'title' => 'Confirm missing profile context',
                'detail' => 'Check missing education, company, or current job details before final recommendation.',
            ]);
        }

        if (($criteria->get('Highest qualification')['status'] ?? null) !== 'pass') {
            $focus->push([
                'title' => 'Confirm qualification eligibility',
                'detail' => 'Highest qualification does not match the current Diploma-starting rule and should be verified.',
            ]);
        }

        if ($focus->isEmpty()) {
            $focus->push([
                'title' => 'Proceed with academic suitability review',
                'detail' => 'The system found no major evidence gaps, so evaluator can focus on academic readiness and admission fit.',
            ]);
        }

        return $focus->values();
    }

    private function buildNextActions(array $classification, Collection $gaps, Collection $criticalFailures): Collection
    {
        if ($classification['level'] === 'high') {
            return collect([
                'Resolve high-severity eligibility flags before assigning or finalizing the application.',
                'Request clarification or additional evidence from the applicant where required.',
                'Record admin justification if the application is still routed to evaluator review.',
            ]);
        }

        if ($classification['level'] === 'low') {
            return collect([
                'Route to evaluator review with normal priority.',
                'Use the evaluator brief as the first-page summary during assessment.',
                'Proceed to final decision after evaluator recommendation is submitted.',
            ]);
        }

        return collect([
            'Route to evaluator review after checking low or medium evidence gaps.',
            'Ask evaluator to focus on the listed weak areas.',
            'Use final decision remarks to document how the gaps were handled.',
        ]);
    }

    private function criterion(
        string $name,
        string $status,
        int $points,
        int $maxPoints,
        string $value,
        string $message,
        bool $critical
    ): array {
        return [
            'name' => $name,
            'status' => $status,
            'points' => $points,
            'max_points' => $maxPoints,
            'value' => $value,
            'message' => $message,
            'critical' => $critical,
        ];
    }

    private function dateValue($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
