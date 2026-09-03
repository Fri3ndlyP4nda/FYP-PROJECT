@extends('layouts.app')

@section('content')
    @php
        $totalPapers = $papers->count();
        $activePapers = $papers->where('status', 'active')->count();
        $inactivePapers = $papers->where('status', '!=', 'active')->count();
    @endphp

    <div class="container papers-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Evaluator Module</span>
                <h2>Assessment Papers</h2>
                <p class="muted page-hero-text">
                    Manage uploaded assessment papers, review linked application records, and open PDF files for
                    verification.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                <a href="{{ route('evaluator.applications.index') }}" class="btn">Assigned Applications</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="papers-stats-grid">
            <div class="papers-stat-card">
                <span>Total Papers</span>
                <strong>{{ $totalPapers }}</strong>
            </div>
            <div class="papers-stat-card">
                <span>Active Papers</span>
                <strong>{{ $activePapers }}</strong>
            </div>
            <div class="papers-stat-card">
                <span>Inactive Papers</span>
                <strong>{{ $inactivePapers }}</strong>
            </div>
        </section>

        <section class="table-card">
            <div class="table-card-header">
                <div>
                    <h3>Paper Library</h3>
                    <p>All assessment papers uploaded by evaluators.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Application ID</th>
                            <th>File</th>
                            <th>Status</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($papers as $paper)
                            <tr>
                                <td>
                                    <div class="paper-title-cell">
                                        <div class="paper-icon">PDF</div>
                                        <div>
                                            <strong class="paper-title">{{ $paper->title }}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="app-id-badge">{{ $paper->application_id }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('files.paper', $paper->_id) }}" target="_blank"
                                        class="paper-file-link">
                                        View PDF
                                    </a>
                                </td>
                                <td>
                                    @if (($paper->status ?? '') === 'active')
                                        <span class="badge badge-approved">Active</span>
                                    @else
                                        <span class="badge badge-pending">{{ ucfirst($paper->status ?? 'Unknown') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('evaluator.assessment.papers.destroy', $paper->_id) }}" style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--bad); border-color: #fca5a5;" onclick="return confirm('Are you sure you want to delete this paper? This action cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="table-empty">
                                        <div class="empty-mark small-empty-mark">01</div>
                                        <h4>No assessment papers found</h4>
                                        <p>There are currently no uploaded assessment papers.</p>
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
