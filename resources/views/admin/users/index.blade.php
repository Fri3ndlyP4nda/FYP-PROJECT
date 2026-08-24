@extends('layouts.app')

@section('content')
    @php
        $totalUsers = $users->count();
        $students = $users->where('role', 'student')->count();
        $evaluators = $users->where('role', 'evaluator')->count();
        $admins = $users->where('role', 'admin')->count();
    @endphp

    <div class="container user-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Admin Management</span>
                <h2>Manage Users</h2>
                <p class="muted page-hero-text">
                    View registered users, monitor their roles, and manage system access across the APEL platform.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                <a href="{{ route('admin.applications.index') }}" class="btn">Manage Applications</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="user-stats-grid">
            <div class="user-stat-card">
                <span>Total Users</span>
                <strong>{{ $totalUsers }}</strong>
            </div>
            <div class="user-stat-card">
                <span>Students</span>
                <strong>{{ $students }}</strong>
            </div>
            <div class="user-stat-card">
                <span>Evaluators</span>
                <strong>{{ $evaluators }}</strong>
            </div>
            <div class="user-stat-card">
                <span>Admins</span>
                <strong>{{ $admins }}</strong>
            </div>
        </section>

        <section class="table-card">
            <div class="table-card-header">
                <div>
                    <h3>User Directory</h3>
                    <p>All registered users currently available in the system.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            @if (Route::has('admin.users.edit') || Route::has('admin.users.destroy'))
                                <th style="width: 220px;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $index => $user)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong class="user-name">{{ $user->name }}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if ($user->role === 'student')
                                        <span class="role-badge role-student">Student</span>
                                    @elseif ($user->role === 'evaluator')
                                        <span class="role-badge role-evaluator">Evaluator</span>
                                    @elseif ($user->role === 'admin')
                                        <span class="role-badge role-admin">Admin</span>
                                    @else
                                        <span class="role-badge">{{ ucfirst($user->role) }}</span>
                                    @endif
                                </td>

                                @if (Route::has('admin.users.edit') || Route::has('admin.users.destroy'))
                                    <td>
                                        <div class="table-actions">
                                            @if (Route::has('admin.users.edit'))
                                                <a href="{{ route('admin.users.edit', $user->_id ?? $user->id) }}"
                                                    class="btn btn-sm">
                                                    Edit
                                                </a>
                                            @endif

                                            @if (Route::has('admin.users.destroy'))
                                                <form action="{{ route('admin.users.destroy', $user->_id ?? $user->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete this user?');"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-secondary btn-sm">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ Route::has('admin.users.edit') || Route::has('admin.users.destroy') ? '5' : '4' }}">
                                    <div class="table-empty">
                                        <div class="empty-mark small-empty-mark">01</div>
                                        <h4>No users found</h4>
                                        <p>There are currently no registered users in the system.</p>
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
