@extends('layouts.app')

@section('content')
    @php
        $total = $applications->count();
        $pending = $applications->where('status', 'pending')->count();
        $approved = $applications->where('status', 'approved')->count();
        $rejected = $applications->where('status', 'rejected')->count();
        $assigned = $applications->whereNotNull('evaluator_id')->count();
    @endphp

    <style>
        .filter-btn:hover:not(.active) {
            color: #6e1730 !important;
            background: rgba(139, 30, 63, 0.04) !important;
        }
    </style>

    <div class="container admin-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Admin Management</span>
                <h2>Manage Applications</h2>
                <p class="muted page-hero-text">
                    Review student submissions, monitor workflow progress, and assign evaluators based on APEL A or APEL C
                    process needs.
                </p>
            </div>

            <div class="hero-actions" style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('admin.reports.apel_a') }}" target="_blank" class="btn btn-secondary">
                    📊 Export APEL.A Report
                </a>
                <a href="{{ route('admin.reports.apel_c') }}" target="_blank" class="btn btn-secondary">
                    📊 Export APEL.C Report
                </a>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <section class="admin-stats-grid">
            <div class="admin-stat-card">
                <span>Total Applications</span>
                <strong>{{ $total }}</strong>
            </div>
            <div class="admin-stat-card">
                <span>Pending</span>
                <strong>{{ $pending }}</strong>
            </div>
            <div class="admin-stat-card">
                <span>Approved</span>
                <strong>{{ $approved }}</strong>
            </div>
            <div class="admin-stat-card">
                <span>Rejected</span>
                <strong>{{ $rejected }}</strong>
            </div>
            <div class="admin-stat-card">
                <span>Assigned Evaluator</span>
                <strong>{{ $assigned }}</strong>
            </div>
        </section>

        <section class="table-card">
            <div class="table-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3>Application Overview</h3>
                    <p>Open each application to continue the correct APEL workflow.</p>
                </div>

                {{-- Queue Filters --}}
                <div class="queue-filters" style="display: flex; gap: 8px; background: #f2e7ea; padding: 4px; border-radius: 10px;">
                    <button type="button" class="filter-btn active" data-filter="all" style="border: none; background: #ffffff; color: #6e1730; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                        All ({{ $total }})
                    </button>
                    <button type="button" class="filter-btn" data-filter="APEL A" style="border: none; background: transparent; color: #837e75; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        APEL A ({{ $applications->where('application_type', 'APEL A')->count() }})
                    </button>
                    <button type="button" class="filter-btn" data-filter="APEL C" style="border: none; background: transparent; color: #837e75; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        APEL C ({{ $applications->where('application_type', 'APEL C')->count() }})
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Program / Course</th>
                            <th>Year</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Workflow Stage</th>
                            <th>Evaluator</th>
                            <th style="width: 220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $application)
                            <tr>
                                <td>{{ \App\Models\User::where('_id', $application->user_id)->value('name') ?? 'Unknown' }}
                                </td>

                                <td>
                                    @if ($application->application_type === 'APEL A')
                                        <span class="type-badge type-apel-a">APEL A</span>
                                    @else
                                        <span class="type-badge type-apel-c">APEL C</span>
                                    @endif
                                </td>

                                <td>{{ $application->program_applied }}</td>
                                <td>
                                    <strong>{{ $application->target_year ?? date('Y', strtotime($application->submission_date)) }}</strong>
                                </td>
                                <td>{{ $application->submission_date }}</td>

                                <td>
                                    @php
                                        $status = $application->status ?? 'Pre-Application Submitted';
                                    @endphp

                                    @if (str_contains(strtolower($status), 'approved'))
                                        <span class="badge badge-approved">{{ $status }}</span>
                                    @elseif (str_contains(strtolower($status), 'rejected') || str_contains(strtolower($status), 'failed'))
                                        <span class="badge badge-rejected">{{ $status }}</span>
                                    @else
                                        <span class="badge badge-pending">{{ $status }}</span>
                                    @endif

                                    @if (($application->appeal_status ?? null) === 'submitted')
                                        <br><br>

                                        <span class="badge badge-pending">
                                            Appeal Submitted
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if (in_array($application->status ?? '', ['Final Approved', 'Final Rejected']))
                                        <span class="stage-badge">Completed</span>
                                    @elseif ($application->application_type === 'APEL A')
                                        <span class="stage-badge">
                                            {{ ucfirst(str_replace('_', ' ', $application->review_stage ?? 'submitted')) }}
                                        </span>
                                    @else
                                        <span class="stage-badge">
                                            {{ $application->evaluator_id ? 'Ready for assessment flow' : 'Waiting for assignment' }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @php
                                         $eval1 = $application->evaluator_id ? \App\Models\User::where('_id', $application->evaluator_id)->value('name') : null;
                                         $eval2 = $application->evaluator_2_id ? \App\Models\User::where('_id', $application->evaluator_2_id)->value('name') : null;
                                         $evalNames = 'Not Assigned';
                                         if ($eval1 && $eval2) {
                                             $evalNames = "{$eval1} & {$eval2}";
                                         } elseif ($eval1) {
                                             $evalNames = $eval1;
                                         }
                                     @endphp
                                    {{ $evalNames }}
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.applications.assign.form', $application->_id) }}"
                                            class="btn btn-sm">
                                            Manage
                                        </a>

                                        @if (in_array($application->status ?? 'Pre-Application Submitted', ['Pre-Application Submitted', 'Under Advisor Review']))
                                            <form method="POST"
                                                action="{{ route('admin.applications.update_status', $application->_id) }}"
                                                style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="status" value="Advisor Approved">
                                                <button type="submit" class="btn btn-sm">Approve</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('admin.applications.update_status', $application->_id) }}"
                                                style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="status" value="Advisor Rejected">
                                                <button type="submit" class="btn btn-sm btn-secondary">Reject</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-records-row">
                                <td colspan="8">
                                    <div class="table-empty">
                                        <div class="empty-mark small-empty-mark">01</div>
                                        <h4>No applications found</h4>
                                        <p>No student applications are available right now.</p>
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
                        btn.style.color = '#837e75';
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
                                <td colspan="8">
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
