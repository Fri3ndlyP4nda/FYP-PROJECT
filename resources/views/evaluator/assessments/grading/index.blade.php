@extends('layouts.app')

@section('content')
    @php
        $totalSubmissions = $submissions->count();
        $gradedSubmissions = $submissions->whereNotNull('graded_at')->count();
        $pendingSubmissions = $totalSubmissions - $gradedSubmissions;
    @endphp

    <div class="container grading-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">APEL C Grading</span>
                <h2>Assessment Submissions</h2>
                <p class="muted page-hero-text">
                    Review uploaded student answer files and complete the grading process for APEL C applications.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn">Assessment Papers</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="papers-stats-grid">
            <div class="papers-stat-card">
                <span>Total Submissions</span>
                <strong>{{ $totalSubmissions }}</strong>
            </div>
            <div class="papers-stat-card">
                <span>Pending Grading</span>
                <strong>{{ $pendingSubmissions }}</strong>
            </div>
            <div class="papers-stat-card">
                <span>Graded</span>
                <strong>{{ $gradedSubmissions }}</strong>
            </div>
        </section>

        <section class="table-card">
            <div class="table-card-header">
                <div>
                    <h3>Submission Queue</h3>
                    <p>All uploaded APEL C answer files waiting for evaluator review.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Student</th>
                            <th>Answer File</th>
                            <th>Score</th>
                            <th>Result</th>
                            <th>Status</th>
                            <th style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            <tr>
                                <td>
                                    <span class="app-id-badge">{{ $submission->application_id }}</span>
                                </td>

                                <td>
                                    {{ \App\Models\User::where('_id', $submission->student_id)->value('name') ?? 'Unknown' }}
                                </td>

                                <td>
                                    @if ($submission->answer_file)
                                        <a href="{{ route('files.submission', $submission->_id) }}" target="_blank"
                                            class="paper-file-link">
                                            View Answer
                                        </a>
                                    @else
                                        <span class="stage-badge">No file</span>
                                    @endif
                                </td>

                                <td>{{ $submission->score ?? '-' }}</td>

                                <td>
                                    @if ($submission->result === 'pass')
                                        <span class="badge badge-approved">Pass</span>
                                    @elseif ($submission->result === 'fail')
                                        <span class="badge badge-rejected">Fail</span>
                                    @else
                                        <span class="badge badge-pending">Pending</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($submission->graded_at)
                                        <span class="stage-badge">Graded</span>
                                    @else
                                        <span class="stage-badge">Awaiting grading</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}"
                                        class="btn btn-sm">
                                        {{ $submission->graded_at ? 'View Grade' : 'Grade Now' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="table-empty">
                                        <div class="empty-mark small-empty-mark">01</div>
                                        <h4>No submissions found</h4>
                                        <p>There are currently no APEL C submissions available for grading.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
