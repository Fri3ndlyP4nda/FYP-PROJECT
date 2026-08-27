@extends('layouts.app')


@section('content')
    <div class="container dashboard-shell">
        <!-- Banner Header -->
        <section class="dashboard-banner">
            <div class="banner-content">
                <span class="dashboard-pill">🔍 Evaluator Workspace</span>
                <h2>Welcome back, {{ auth()->user()->name }}</h2>
                <p>
                    Review assigned student applications, manage assessment papers, and evaluate portfolio submissions
                    from one centralized control dashboard.
                </p>

                <div class="banner-actions">
                    <a href="{{ route('evaluator.applications.index') }}" class="btn">Assigned Applications</a>
                    <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-light">Start Grading</a>
                </div>
            </div>

            <div class="banner-side">
                <div class="mini-profile-card">
                    <span class="mini-label">Account Role</span>
                    <strong>Evaluator</strong>
                    <small>Evaluator Management Portal</small>
                </div>
            </div>
        </section>

        <!-- Evaluator Counters Summary -->
        <div class="evaluator-stats-grid">
            <div class="admin-stat-card">
                <span>📋 Assigned Claims</span>
                <strong>{{ $totalClaims }}</strong>
            </div>

            <div class="admin-stat-card">
                <span>✅ Graded Papers</span>
                <strong>{{ $gradedCount }}</strong>
            </div>

            <div class="admin-stat-card">
                <span>⏳ Pending Workload</span>
                <strong>{{ $pendingCount }}</strong>
            </div>

            <div class="admin-stat-card">
                <span>🎯 Average Score</span>
                <strong>{{ $avgScore }}</strong>
            </div>
        </div>

        <!-- Informative Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">📋 Assigned Work</span>
                <strong>Review Portfolios</strong>
                <p>Access applications that have been allocated specifically for your expert evaluation.</p>
            </div>

            <div class="stat-card">
                <span class="stat-title">🛠️ Assessment Tool</span>
                <strong>Create Materials</strong>
                <p>Manage and draft exam papers, interview matrices, and evaluation structures efficiently.</p>
            </div>

            <div class="stat-card">
                <span class="stat-title">💯 Grading Portal</span>
                <strong>Evaluate Submissions</strong>
                <p>Grade student answers, mark scores, submit evaluation findings, and record remarks.</p>
            </div>
        </section>

        <!-- Actions Panel -->
        <section class="action-panel">
            <div class="panel-heading">
                <h3>Quick Actions</h3>
                <p>Open the modules you need to evaluate submissions or manage exam materials.</p>
            </div>

            <div class="action-grid action-grid-3">
                <a href="{{ route('evaluator.applications.index') }}" class="action-card">
                    <div class="action-icon">📂</div>
                    <div>
                        <h4>Assigned Applications</h4>
                        <p>Review all student applications assigned to your account.</p>
                    </div>
                </a>

                <a href="{{ route('evaluator.assessment.papers.index') }}" class="action-card">
                    <div class="action-icon">📝</div>
                    <div>
                        <h4>Assessment Papers</h4>
                        <p>Create and manage paper-based assessment materials for candidates.</p>
                    </div>
                </a>

                <a href="{{ route('evaluator.assessment.grading.index') }}" class="action-card">
                    <div class="action-icon">💯</div>
                    <div>
                        <h4>Grade Submissions</h4>
                        <p>Evaluate student portfolio answers and record scores or grades.</p>
                    </div>
                </a>
            </div>
        </section>
    </div>
@endsection
