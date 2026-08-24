@extends('layouts.app')

@section('content')
    <div class="container admin-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">APEL A Management</span>
                <h2>APEL A Applications</h2>
                <p class="muted page-hero-text">
                    Manage APEL A admission applications, evaluator assignments, and final decisions.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">All Applications</a>
            </div>
        </section>

        <section class="table-card">
            <div class="table-card-header">
                <h3>APEL A Records</h3>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Stage</th>
                            <th>Final Decision</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>{{ $application->program_applied }}</td>
                                <td>{{ \App\Models\User::where('_id', $application->user_id)->value('name') }}</td>

                                <td>
                                    <span class="badge badge-{{ $application->status }}">
                                        {{ ucfirst($application->status) }}
                                    </span>
                                </td>

                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $application->review_stage ?? 'submitted')) }}
                                </td>

                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $application->final_decision ?? 'pending')) }}
                                </td>

                                <td>
                                    <a href="{{ route('admin.applications.assign.form', $application->_id) }}"
                                        class="btn btn-sm">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
