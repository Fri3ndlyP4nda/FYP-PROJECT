@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    <div class="container dashboard-shell">
        <!-- Banner Header -->
        <section class="dashboard-banner">
            <div class="banner-content">
                <span class="dashboard-pill">⚙️ Admin Workspace</span>
                <h2>Welcome back, {{ auth()->user()->name }}</h2>
                <p>
                    Oversee the entire APEL management system, monitor application queues,
                    and assign evaluators to pending student claims.
                </p>

                <div class="banner-actions">
                    <a href="{{ route('admin.applications.index') }}" class="btn">Manage Applications</a>
                </div>
            </div>

            <div class="banner-side">
                <div class="mini-profile-card">
                    <span class="mini-label">Account Role</span>
                    <strong>Admin</strong>
                    <small>System Administrator Panel</small>
                </div>
            </div>
        </section>

        <!-- Informative Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">📂 Application Control</span>
                <strong>Monitor Flow</strong>
                <p>Track student submissions, verify documentation uploads, and route them to evaluators.</p>
            </div>

            <div class="stat-card">
                <span class="stat-title">💳 Fee Verification</span>
                <strong>Verify Payments</strong>
                <p>Verify students' processing and credit transfer payments submitted via Payhub.</p>
            </div>

            <div class="stat-card">
                <span class="stat-title">🔄 Coordinate Flow</span>
                <strong>Workflow Pipeline</strong>
                <p>Keep the prior experiential evaluation and verification workflow structured and efficient.</p>
            </div>

        </section>

        <!-- Administrative Counters Summary -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <span>📊 Total Applications</span>
                <strong>{{ $totalApplications }}</strong>
            </div>

            <div class="admin-stat-card">
                <span>📁 APEL A Type</span>
                <strong>{{ $apelACount }}</strong>
            </div>

            <div class="admin-stat-card">
                <span>✅ Approved APEL A</span>
                <strong>{{ $apelAApproved }}</strong>
            </div>
        </div>


        @php
            $metrics = $workflowMetrics ?? [
                'active_count' => 0,
                'delayed_count' => 0,
                'unassigned_ready_count' => 0,
                'pending_payment_count' => 0,
                'average_processing_days' => 0,
                'bottlenecks' => collect(),
            ];
        @endphp

        <section class="action-panel" style="margin-bottom: 28px;">
            <div class="panel-heading">
                <h3>Workflow Efficiency Monitor</h3>
                <p>Decision-support indicators for delayed cases, assignment readiness, and processing bottlenecks.</p>
            </div>

            <div class="admin-stats-grid" style="margin-top: 14px;">
                <div class="admin-stat-card">
                    <span>Active Cases</span>
                    <strong>{{ $metrics['active_count'] }}</strong>
                </div>

                <div class="admin-stat-card">
                    <span>Delayed Over 7 Days</span>
                    <strong>{{ $metrics['delayed_count'] }}</strong>
                </div>

                <div class="admin-stat-card">
                    <span>Ready for Assignment</span>
                    <strong>{{ $metrics['unassigned_ready_count'] }}</strong>
                </div>

                <div class="admin-stat-card">
                    <span>Avg Processing Days</span>
                    <strong>{{ $metrics['average_processing_days'] }}</strong>
                </div>
            </div>

            <div class="table-card" style="margin-top: 14px;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Workflow Stage</th>
                                <th style="width: 160px;">Active Count</th>
                                <th>Operational Meaning</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($metrics['bottlenecks'] as $bottleneck)
                                <tr>
                                    <td style="font-weight: 600;">{{ $bottleneck['stage'] }}</td>
                                    <td>{{ $bottleneck['count'] }}</td>
                                    <td style="font-size: 13.5px; color: var(--ink-2);">
                                        This stage currently has queue pressure and should be checked first.
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--ink-4); padding: 20px;">
                                        No active workflow bottlenecks detected.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Visual Statistics Charts -->
        <div class="dashboard-charts-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 28px;">
            <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid rgba(139, 30, 63, 0.05); box-shadow: 0 10px 24px rgba(0, 0, 0, 0.02);">
                <h4 style="margin-bottom: 16px; color: #30030f; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Application Distribution</h4>
                <div id="type-chart"></div>
            </div>
            <div class="card" style="padding: 24px; border-radius: 20px; border: 1px solid rgba(139, 30, 63, 0.05); box-shadow: 0 10px 24px rgba(0, 0, 0, 0.02);">
                <h4 style="margin-bottom: 16px; color: #30030f; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Accreditation Success Rate</h4>
                <div id="approval-chart"></div>
            </div>
        </div>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Type Distribution
                    var typeOptions = {
                        chart: {
                            type: 'donut',
                            height: 280
                        },
                        series: [{{ $apelACount }}, {{ $apelCCount }}],
                        labels: ['APEL A', 'APEL C'],
                        colors: ['#6e1730', '#cc5c7d'],
                        legend: {
                            position: 'bottom'
                        },
                        responsive: [{
                            breakpoint: 480,
                            options: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }]
                    };

                    var typeChart = new ApexCharts(document.querySelector("#type-chart"), typeOptions);
                    typeChart.render();

                    // Approval Status
                    var approvalOptions = {
                        chart: {
                            type: 'bar',
                            height: 280,
                            toolbar: {
                                show: false
                            }
                        },
                        series: [{
                            name: 'Approved',
                            data: [{{ $apelAApproved }}, {{ $apelCApproved }}]
                        }, {
                            name: 'Total',
                            data: [{{ $apelACount }}, {{ $apelCCount }}]
                        }],
                        xaxis: {
                            categories: ['APEL A', 'APEL C'],
                        },
                        colors: ['#146b45', '#6e1730'],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '55%',
                                borderRadius: 4
                            },
                        },
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            position: 'bottom'
                        }
                    };

                    var approvalChart = new ApexCharts(document.querySelector("#approval-chart"), approvalOptions);
                    approvalChart.render();
                });
            </script>
        @endpush

        <!-- Actions Panel -->
        <section class="action-panel">
            <div class="panel-heading">
                <h3>Quick Actions</h3>
                <p>Access the main system administration screens to track APEL claims and verify submissions.</p>
            </div>

            <div class="action-grid" style="grid-template-columns: 1fr;">
                <a href="{{ route('admin.applications.index') }}" class="action-card">
                    <div class="action-icon">📂</div>
                    <div>
                        <h4>Manage Applications</h4>
                        <p>View the list of all applications, assign evaluators, and finalize grades.</p>
                    </div>
                </a>
            </div>
        </section>

        <!-- System Activity Audit Feed -->
        <section class="action-panel" style="margin-top: 28px;">
            <div class="panel-heading">
                <h3>System Activity Feed</h3>
                <p>Recent administrative actions and assessment grading logs recorded in the system.</p>
            </div>

            <div class="table-card" style="margin-top: 14px;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 150px;">Timestamp</th>
                                <th style="width: 150px;">User</th>
                                <th style="width: 120px;">Role</th>
                                <th style="width: 180px;">Action</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activityLogs as $log)
                                <tr>
                                    <td style="font-size: 13px; color: var(--ink-3); white-space: nowrap;">
                                        {{ $log->created_at ? $log->created_at->format('Y-m-d H:i') : 'N/A' }}
                                    </td>
                                    <td style="font-weight: 600;">{{ $log->user_name }}</td>
                                    <td>
                                        @if($log->user_role === 'admin')
                                            <span class="role-badge role-admin">Admin</span>
                                        @elseif($log->user_role === 'evaluator')
                                            <span class="role-badge role-evaluator">Evaluator</span>
                                        @else
                                            <span class="role-badge role-student">Student</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-submitted">{{ $log->action }}</span>
                                    </td>
                                    <td style="font-size: 13.5px; color: var(--ink-2);">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--ink-4); padding: 24px;">
                                        No recent system activities logged.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
