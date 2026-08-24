@extends('layouts.app')

@section('content')
    @php
        $total = $applications->count();

        $pending = $applications
            ->filter(function ($application) {
                return !str_contains(strtolower($application->status ?? ''), 'approved') &&
                    !str_contains(strtolower($application->status ?? ''), 'rejected');
            })
            ->count();

        $approved = $applications
            ->filter(function ($application) {
                return str_contains(strtolower($application->status ?? ''), 'approved');
            })
            ->count();

        $rejected = $applications
            ->filter(function ($application) {
                return str_contains(strtolower($application->status ?? ''), 'rejected');
            })
            ->count();
    @endphp

    <div class="container app-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Student Applications</span>
                <h2>My APEL Applications</h2>
                <p class="muted page-hero-text">
                    Review your submission history, check evaluation progress, and open the next related step for each
                    application.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                <a href="{{ route('student.applications.create') }}" class="btn">Submit New Application</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="mini-stats-grid">
            <div class="mini-stat-card">
                <span>Total Applications</span>
                <strong>{{ $total }}</strong>
            </div>

            <div class="mini-stat-card">
                <span>Pending</span>
                <strong>{{ $pending }}</strong>
            </div>

            <div class="mini-stat-card">
                <span>Approved</span>
                <strong>{{ $approved }}</strong>
            </div>

            <div class="mini-stat-card">
                <span>Rejected</span>
                <strong>{{ $rejected }}</strong>
            </div>
        </section>

        @forelse ($applications as $application)
            <section class="record-card">
                <div class="record-top">
                    <div>
                        <p class="record-kicker">{{ $application->application_type }}</p>
                        <h3>{{ $application->program_applied }}</h3>
                    </div>

                    <div class="record-top-right">
                        @php
                            $status = $application->status ?? 'Pre-Application Submitted';
                        @endphp

                        @if (str_contains(strtolower($status), 'approved') || str_contains(strtolower($status), 'verified'))
                            <span class="badge badge-approved">{{ $status }}</span>
                        @elseif (str_contains(strtolower($status), 'rejected') || str_contains(strtolower($status), 'failed'))
                            <span class="badge badge-rejected">{{ $status }}</span>
                        @else
                            <span class="badge badge-pending">{{ $status }}</span>
                        @endif
                    </div>
                </div>

                <div class="record-meta-grid">
                    <div class="meta-box">
                        <span class="meta-label">Submission Date</span>
                        <strong>{{ $application->submission_date }}</strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Assigned Evaluator</span>
                        <strong>
                            @php
                                $eval1 = $application->evaluator_id ? \App\Models\User::where('_id', $application->evaluator_id)->value('name') : null;
                                $eval2 = $application->evaluator_2_id ? \App\Models\User::where('_id', $application->evaluator_2_id)->value('name') : null;
                            @endphp
                            @if ($eval1 && $eval2)
                                {{ $eval1 }} & {{ $eval2 }}
                            @elseif ($eval1)
                                {{ $eval1 }}
                            @else
                                Not Assigned
                            @endif
                        </strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Reviewed At</span>
                        <strong>{{ $application->reviewed_at ?? 'Not reviewed yet' }}</strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Payment Status</span>
                        <strong>{{ ucfirst($application->payment_status ?? 'pending') }}</strong>
                    </div>
                </div>



                @if ($application->status !== 'Draft' && ($application->payment_status ?? 'pending') === 'pending')
                    <div class="alert alert-warning" style="background-color: #fffbeb; border: 1px solid #fef3c7; color: #b45309; padding: 12px 16px; border-radius: 12px; margin-top: 15px; margin-bottom: 15px; font-size: 13.5px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>⚠️</span>
                            <strong>Payment Required to Proceed</strong>
                        </div>
                        <p style="margin: 0; color: #d97706;">
                            Please upload your payment receipt to proceed with the application evaluation. 
                            @if ($application->application_type === 'APEL A')
                                <a href="{{ route('student.apel_a.show', $application->_id) }}" style="font-weight: 700; text-decoration: underline; color: #b45309;">Upload Receipt Here →</a>
                            @else
                                <a href="{{ route('student.apel_c.show', $application->_id) }}" style="font-weight: 700; text-decoration: underline; color: #b45309;">Upload Receipt Here →</a>
                            @endif
                        </p>
                    </div>
                @elseif ($application->status !== 'Draft' && (($application->payment_status ?? '') === 'submitted'))
                    <div class="alert alert-info" style="background-color: #f0fdfa; border: 1px solid #ccfbf1; color: #0d9488; padding: 12px 16px; border-radius: 12px; margin-top: 15px; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
                        <span>ℹ️</span>
                        <span>Payment receipt submitted. Awaiting verification by the Faculty Academic Office.</span>
                    </div>
                @endif

                <div class="record-panel">
                    <h4>Internal Application Form</h4>

                    @if ($application->application_type === 'APEL A')
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 12px; font-size: 13.5px; color: #4b5563;">
                            <div><strong>Age:</strong> {{ $application->age ?? 'Not provided' }}</div>
                            <div><strong>University:</strong> {{ $application->university_name ?? 'Not provided' }}</div>
                            <div><strong>Company:</strong> {{ $application->company_name ?? 'Not provided' }}</div>
                        </div>

                        <p class="feedback-text">
                            <strong>Highest Qualification:</strong>
                            {{ $application->highest_qualification ?? 'Not provided' }}
                        </p>

                        <p class="feedback-text">
                            <strong>Current Job:</strong>
                            {{ $application->current_job ?? 'Not provided' }}
                        </p>

                        <p class="feedback-text">
                            <strong>Working Experience:</strong>
                            {{ $application->working_experience_years ?? '0' }} year(s)
                        </p>
                    @else
                        <p class="feedback-text">
                            <strong>Course:</strong>
                            {{ $application->credit_course_name ?? 'Not provided' }}
                            @if ($application->credit_course_code)
                                ({{ $application->credit_course_code }})
                            @endif
                        </p>

                        <p class="feedback-text">
                            <strong>Self-Assessment:</strong>
                            {{ $application->self_assessment_statement ?? 'Not provided' }}
                        </p>
                    @endif
                </div>

                <div class="record-panel">
                    <h4>Evaluator Feedback</h4>
                    <p class="feedback-text">
                        {{ $application->evaluator_feedback ?? 'No feedback has been provided yet.' }}
                    </p>
                </div>

                <div class="record-footer">
                    @if ($application->status === 'Draft')
                        <a href="{{ route('student.applications.edit', $application->_id) }}" class="btn">
                            ✏️ Continue Application
                        </a>
                    @else
                        <a href="{{ route('student.applications.print', $application->_id) }}" target="_blank" class="btn btn-secondary">
                            🖨️ Print Portfolio
                        </a>
                        @if ($application->application_type === 'APEL A')
                            <a href="{{ route('student.apel_a.show', $application->_id) }}" class="btn">
                                View APEL A Result
                            </a>
                        @elseif ($application->application_type === 'APEL C')
                            <a href="{{ route('student.apel_c.show', $application->_id) }}" class="btn">
                                View APEL C Result
                            </a>

                            @php
                                $assessmentPaper = \App\Models\AssessmentPaper::where(
                                    'application_id',
                                    (string) $application->_id,
                                )
                                    ->where('status', 'active')
                                    ->first();
                            @endphp

                            @if ($assessmentPaper)
                                <a href="{{ route('student.assessment.show', $application->_id) }}" class="btn btn-secondary">
                                    View Assessment
                                </a>
                            @endif
                        @endif
                    @endif
                </div>
            </section>
        @empty
            <section class="empty-state-card">
                <div class="empty-mark">01</div>
                <h3>No applications submitted yet</h3>
                <p>
                    You have not submitted any APEL application yet. Start your first submission to begin the evaluation
                    process.
                </p>
                <a href="{{ route('student.applications.create') }}" class="btn">Create First Application</a>
            </section>
        @endforelse
    </div>
@endsection
