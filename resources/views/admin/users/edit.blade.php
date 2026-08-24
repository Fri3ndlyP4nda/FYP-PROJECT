@extends('layouts.app')

@section('content')
    <div class="container app-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">ADMIN MANAGEMENT</span>
                <h2>Edit User Role</h2>
                <p class="muted page-hero-text">
                    Update the selected user's system role and control their access within the APEL platform.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to User Management</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="padding-left: 18px; margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-split-layout">
            <div class="card form-main-card">
                <div class="record-meta-grid">
                    <div class="meta-box">
                        <span class="meta-label">User Name</span>
                        <strong>{{ $user->name }}</strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Email Address</span>
                        <strong>{{ $user->email }}</strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Current Role</span>
                        <strong>{{ ucfirst($user->role) }}</strong>
                    </div>
                </div>

                <div class="record-panel" style="margin-top: 18px;">
                    <h4>Update Role</h4>
                    <p class="feedback-text" style="margin-bottom: 16px;">
                        Choose a new role for this user. This will affect which dashboard and system features they can
                        access.
                    </p>

                    <form action="{{ route('admin.users.update', $user->_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label for="role">Select Role</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="student" {{ $user->role === 'student' ? 'selected' : '' }}>Student</option>
                                <option value="evaluator" {{ $user->role === 'evaluator' ? 'selected' : '' }}>Evaluator
                                </option>
                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>

                        <div class="form-submit-row">
                            <button type="submit" class="btn">Update Role</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <aside class="info-side-card">
                <span class="side-label">Role Guide</span>
                <h3>Role Permissions</h3>

                <ul class="check-list">
                    <li><strong>Student</strong> can submit APEL applications and view their own progress.</li>
                    <li><strong>Evaluator</strong> can review assigned applications and grade assessments.</li>
                    <li><strong>Admin</strong> can manage users, assign evaluators, and finalize decisions.</li>
                </ul>

                <div class="tip-box">
                    <strong>Reminder</strong>
                    <p>
                        Make sure the selected role matches the user's actual responsibility before saving changes.
                    </p>
                </div>
            </aside>
        </div>
    </div>
@endsection
