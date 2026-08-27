@extends('layouts.app')

@section('content')
    @php
        $total = $applications->count();
        $pending = $applications->where('status', 'pending')->count();
        $approved = $applications->where('status', 'approved')->count();
        $rejected = $applications->where('status', 'rejected')->count();
    @endphp

    <style>
        .filter-btn:hover:not(.active) {
            color: var(--maroon) !important;
            background: rgba(139, 30, 63, 0.04) !important;
        }
    </style>

    <div class="container eval-shell">
        <section class="eval-hero">
            <div>
                <span class="section-pill">Evaluator Review</span>
                <h2>Assigned Applications</h2>
                <p class="muted eval-hero-text">
                    Review assigned applications and continue the correct process based on whether the record is APEL A or
                    APEL C.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn">Assessment Papers</a>
                <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-secondary">Grade Submissions</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="mini-stats-grid">
            <div class="mini-stat-card">
                <span>Total Assigned</span>
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

        <section class="table-card">
            <div class="table-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3>Application Queue</h3>
                    <p>All applications currently assigned to you for review.</p>
                </div>

                {{-- Queue Filters --}}
                <div class="queue-filters" style="display: flex; gap: 8px; background: var(--maroon-tint); padding: 4px; border-radius: 10px;">
                    <button type="button" class="filter-btn active" data-filter="all" style="border: none; background: #ffffff; color: var(--maroon); padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                        All ({{ $total }})
                    </button>
                    <button type="button" class="filter-btn" data-filter="APEL A" style="border: none; background: transparent; color: var(--ink-3); padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        APEL A ({{ $applications->where('application_type', 'APEL A')->count() }})
                    </button>
                    <button type="button" class="filter-btn" data-filter="APEL C" style="border: none; background: transparent; color: var(--ink-3); padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        APEL C ({{ $applications->where('application_type', 'APEL C')->count() }})
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Program Applied</th>
                            <th>Student Name</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Workflow</th>
                            <th style="width: 260px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $application)
                            <tr>
                                <td>
                                    @if ($application->application_type === 'APEL A')
                                        <span class="type-badge type-apel-a">APEL A</span>
                                    @else
                                        <span class="type-badge type-apel-c">APEL C</span>
                                    @endif
                                </td>

                                <td>{{ $application->program_applied }}</td>

                                <td>{{ \App\Models\User::where('_id', $application->user_id)->value('name') ?? 'Unknown' }}
                                </td>

                                <td>{{ $application->submission_date }}</td>

                                <td>
                                    @php
                                        $status = $application->status ?? 'Pre-Application Submitted';
                                        $isFinalized = in_array($application->credit_decision ?? '', ['approved', 'rejected']) || in_array($status, ['Final Approved', 'Final Rejected']);
                                    @endphp

                                    @if (str_contains(strtolower($status), 'approved'))
                                        <span class="badge badge-approved">{{ $status }}</span>
                                    @elseif (str_contains(strtolower($status), 'rejected') || str_contains(strtolower($status), 'failed'))
                                        <span class="badge badge-rejected">{{ $status }}</span>
                                    @else
                                        <span class="badge badge-pending">{{ $status }}</span>
                                    @endif
                                </td>

                                <td>
                                    @if (in_array($status, ['Final Approved', 'Final Rejected']))
                                        <span class="stage-badge">Completed</span>
                                    @elseif ($application->application_type === 'APEL A')
                                        <span class="stage-badge">
                                            {{ ucfirst(str_replace('_', ' ', $application->review_stage ?? 'submitted')) }}
                                        </span>
                                    @else
                                        <span class="stage-badge">
                                            Assessment workflow
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('evaluator.applications.show', $application->_id) }}"
                                            class="btn btn-sm">
                                            {{ $application->application_type === 'APEL A' ? 'Review Admission' : 'Review Application' }}
                                        </a>

                                        @if ($application->application_type === 'APEL C' && ($application->assessment_type ?? '') !== 'portfolio')
                                            @php
                                                $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();
                                                $paperExists = \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)->exists();
                                            @endphp
                                            @if (!$isFinalized)
                                                @if (!($isEvaluator2 && $paperExists))
                                                    <a href="{{ route('evaluator.assessment.papers.create', $application->_id) }}"
                                                        class="btn btn-secondary btn-sm">
                                                        Upload PDF
                                                    </a>
                                                @endif

                                                @php
                                                    $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();
                                                @endphp

                                                @if ($submission)
                                                    <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}"
                                                        class="btn btn-secondary btn-sm"
                                                        style="background-color: var(--maroon-tint); color: var(--maroon); border: 1px solid var(--maroon);">
                                                        Grade Submission
                                                    </a>
                                                @else
                                                    <button type="button" class="btn btn-secondary btn-sm" disabled 
                                                        style="opacity: 0.65; cursor: not-allowed; border: 1px solid var(--line-strong);" 
                                                        title="No submission uploaded yet">
                                                        Grade Submission
                                                    </button>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-records-row">
                                <td colspan="7">
                                    <div class="table-empty">
                                        <div class="empty-mark small-empty-mark">01</div>
                                        <h4>No assigned applications</h4>
                                        <p>There are currently no applications assigned to you.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const rows = document.querySelectorAll('tbody tr');

            filterButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const filter = this.getAttribute('data-filter');

                    // Update button active states and inline styles
                    filterButtons.forEach(btn => {
                        btn.classList.remove('active');
                        btn.style.background = 'transparent';
                        btn.style.color = 'var(--ink-3)';
                        btn.style.boxShadow = 'none';
                    });
                    this.classList.add('active');
                    this.style.background = '#ffffff';
                    this.style.color = '#6e1730';
                    this.style.boxShadow = '0 2px 6px rgba(0,0,0,0.05)';

                    let visibleCount = 0;
                    rows.forEach(row => {
                        if (row.id === 'empty-filter-row' || row.classList.contains('no-records-row')) {
                            return;
                        }

                        const typeBadge = row.querySelector('.type-badge');
                        if (typeBadge) {
                            const type = typeBadge.textContent.trim();
                            if (filter === 'all' || type === filter) {
                                row.style.display = '';
                                visibleCount++;
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });

                    // Manage empty filter row
                    const emptyRow = document.getElementById('empty-filter-row');
                    const noRecordsRow = document.querySelector('.no-records-row');
                    
                    if (noRecordsRow) {
                        return; // If queue was already empty, keep original empty row
                    }

                    if (visibleCount === 0) {
                        if (!emptyRow) {
                            const newEmptyRow = document.createElement('tr');
                            newEmptyRow.id = 'empty-filter-row';
                            newEmptyRow.innerHTML = `
                                <td colspan="7">
                                    <div class="table-empty" style="padding: 40px 0;">
                                        <div class="empty-mark small-empty-mark">01</div>
                                        <h4>No applications found</h4>
                                        <p>There are no ${filter} applications in your queue.</p>
                                    </div>
                                </td>
                            `;
                            document.querySelector('tbody').appendChild(newEmptyRow);
                        } else {
                            emptyRow.querySelector('p').textContent = `There are no ${filter} applications in your queue.`;
                            emptyRow.style.display = '';
                        }
                    } else if (emptyRow) {
                        emptyRow.style.display = 'none';
                    }
                });
            });
        });
    </script>
@endsection
