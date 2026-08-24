@extends('layouts.app')

@section('content')
    <div class="container eval-shell">
        <section class="eval-hero">
            <div>
                <span class="section-pill">APEL A Review</span>
                <h2>APEL A Applications</h2>
                <p class="muted eval-hero-text">
                    Review admission applications and provide recommendations.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">All Applications</a>
            </div>
        </section>

        <section class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Stage</th>
                            <th>Decision</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>{{ $application->program_applied }}</td>

                                <td>{{ ucfirst($application->status) }}</td>

                                <td>{{ ucfirst(str_replace('_', ' ', $application->review_stage ?? 'submitted')) }}</td>

                                <td>{{ ucfirst(str_replace('_', ' ', $application->admission_decision ?? 'pending')) }}</td>

                                <td>
                                    <a href="{{ route('evaluator.applications.show', $application->_id) }}"
                                        class="btn btn-sm">
                                        Review
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
