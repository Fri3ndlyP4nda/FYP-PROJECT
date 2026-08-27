@extends('layouts.app')


@section('content')
    <div class="container dashboard-shell">
        <!-- Banner Header -->
        <section class="dashboard-banner">
            <div class="banner-content">
                <span class="dashboard-pill">🎓 Student Workspace</span>
                <h2>Welcome back, {{ auth()->user()->name }}</h2>
                <p>
                    Manage your APEL journey in one place. Start a new accreditation application, monitor evaluation progress,
                    and review your submission history.
                </p>

                <div class="banner-actions">
                    <a href="{{ route('student.applications.create') }}" class="btn">New Application</a>
                    <a href="{{ route('student.applications.index') }}" class="btn btn-light">My Applications</a>
                </div>
            </div>

            <div class="banner-side">
                <div class="mini-profile-card">
                    <span class="mini-label">Account Role</span>
                    <strong>Student</strong>
                    <small>APEL Accreditation Portal</small>
                </div>
            </div>
        </section>

        <!-- Informative Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">📂 Applications</span>
                <strong>Manage Applications</strong>
                <p>Submit and edit your portfolios, work logs, and experiential credit claims.</p>
            </div>

            <div class="stat-card">
                <span class="stat-title">📈 Progress</span>
                <strong>Track Review Status</strong>
                <p>Monitor where your application is in the advisor or evaluator assessment pipeline.</p>
            </div>

            <div class="stat-card">
                <span class="stat-title">🔔 Notification Updates</span>
                <strong>Stay Informed</strong>
                <p>Get real-time feedback from academic evaluators and advisory councils.</p>
            </div>
        </section>

        <!-- Actions Panel -->
        <section class="action-panel">
            <div class="panel-heading">
                <h3>Quick Actions</h3>
                <p>Choose what you want to do next to accelerate your prior learning assessment.</p>
            </div>

            <div class="action-grid">
                <a href="{{ route('student.applications.create') }}" class="action-card">
                    <div class="action-icon">🚀</div>
                    <div>
                        <h4>Start New Application</h4>
                        <p>Create a new APEL application and submit your professional experience details.</p>
                    </div>
                </a>

                <a href="{{ route('student.applications.index') }}" class="action-card">
                    <div class="action-icon">📂</div>
                    <div>
                        <h4>View Applications</h4>
                        <p>Check the status, edit, or submit payments for your active applications.</p>
                    </div>
                </a>
            </div>
        </section>
    </div>
@endsection
