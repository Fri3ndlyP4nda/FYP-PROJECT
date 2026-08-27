<?php

namespace Tests;

use App\Domain\Apel\ApelStage;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Builders for the records these tests need.
 *
 * Deliberately not Eloquent factories: MongoDB documents here are wide and
 * mostly optional, and a factory that fills every field would obscure which
 * field each test actually depends on. These helpers set only what matters and
 * let the caller override the rest.
 */
trait MakesApelRecords
{
    protected function makeUser(string $role, array $attributes = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'name' => ucfirst($role)." {$n}",
            'email' => "{$role}{$n}@apel.test",
            'password' => Hash::make('TestPassword123'),
            'role' => $role,
        ], $attributes));
    }

    protected function makeStudent(array $attributes = []): User
    {
        return $this->makeUser('student', $attributes);
    }

    protected function makeEvaluator(array $attributes = []): User
    {
        return $this->makeUser('evaluator', $attributes);
    }

    protected function makeAdmin(array $attributes = []): User
    {
        return $this->makeUser('admin', $attributes);
    }

    /**
     * An application owned by $owner. Defaults to a submitted APEL A so that
     * most tests can use it without first walking the workflow forward.
     */
    protected function makeApplication(User $owner, array $attributes = []): Application
    {
        return Application::create(array_merge([
            'user_id' => (string) $owner->_id,
            'student_id' => (string) $owner->_id,
            'application_type' => 'APEL A',
            'stage' => ApelStage::SUBMITTED->value,
            'program_applied' => 'Master of Computer Science (ODL)',
            'submission_date' => now(),
            'status_updated_at' => now(),
        ], $attributes));
    }

    protected function makeApelC(User $owner, array $attributes = []): Application
    {
        return $this->makeApplication($owner, array_merge([
            'application_type' => 'APEL C',
            'credit_course_code' => 'MECS0013',
            'credit_course_name' => 'Software Engineering',
        ], $attributes));
    }

    protected function makePaper(Application $application, User $evaluator, array $attributes = []): AssessmentPaper
    {
        return AssessmentPaper::create(array_merge([
            'application_id' => (string) $application->_id,
            'evaluator_id' => (string) $evaluator->_id,
            'title' => 'Assessment Paper',
            'question_file' => 'assessment_papers/paper.pdf',
            'status' => 'active',
            'submission_deadline' => now()->addDays(7),
        ], $attributes));
    }

    protected function makeSubmission(Application $application, array $attributes = []): AssessmentSubmission
    {
        return AssessmentSubmission::create(array_merge([
            'application_id' => (string) $application->_id,
            'student_id' => (string) $application->user_id,
            'answer_file' => 'assessment_answers/answer.pdf',
            'status' => 'submitted',
            'submitted_at' => now(),
        ], $attributes));
    }
}
