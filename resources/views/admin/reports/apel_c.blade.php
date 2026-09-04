<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEL.C Candidate Summary Report</title>
    <style>
        .print-toolbar {
            background: #ffffff;
            border-bottom: 1px solid #ebdbe0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(139, 30, 63, 0.08);
        }
        .print-toolbar-logo {
            font-size: 18px;
            font-weight: 800;
            color: #6e1730;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-preview-shell {
            background-color: #f1efea;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .print-paper-sheet {
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            padding: 25mm 20mm;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .print-header-brand {
            border-bottom: 3px double #6e1730;
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .print-header-brand h2 {
            font-size: 22px;
            font-weight: 800;
            color: #6e1730;
            margin: 0;
            text-transform: uppercase;
        }
        .print-header-brand p {
            font-size: 13px;
            color: #555555;
            margin: 5px 0 0 0;
            font-weight: 600;
        }
        .stat-grid-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            border: 1px solid #e4e0d8;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background: #fcfbfc;
        }
        .stat-box span {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #8b7280;
            display: block;
            margin-bottom: 6px;
        }
        .stat-box strong {
            font-size: 22px;
            color: #6e1730;
            font-weight: 800;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        .report-table th, .report-table td {
            border: 1px solid #e4e0d8;
            padding: 8px 10px;
            text-align: left;
        }
        .report-table th {
            background-color: #faf8f9;
            color: #6e1730;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .report-table tr:nth-child(even) {
            background-color: #fafbfc;
        }
        @media print {
            .print-toolbar { display: none; }
            .print-preview-shell { padding: 0; background: transparent; }
            .print-paper-sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                width: 100%;
                min-height: auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    @php
        $totalCreditHours = 0;
        foreach($applications as $app) {
            if ($app->status === 'Final Approved') {
                $totalCreditHours += \App\Http\Controllers\Admin\ApplicationManagementController::getCreditHoursFromCourseCode($app->credit_course_code);
            }
        }
    @endphp

    <div class="print-toolbar">
        <div class="print-toolbar-logo">
            <span>⚙️</span> UTM APEL.C Report
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">
                ← Back to List
            </a>
            <a href="{{ route('admin.reports.apel_c.export') }}" class="btn btn-light" style="border: 1px solid #cfc9be;">
                📥 Export CSV
            </a>
            <button onclick="window.print()" class="btn">
                🖨️ Print Summary
            </button>
        </div>
    </div>

    <div class="print-preview-shell">
        <div class="print-paper-sheet">
            <div class="print-header-brand">
                <h2>Universiti Teknologi Malaysia</h2>
                <p>APEL.C (Credit Transfer) Candidate Summary Report</p>
                <p style="font-size: 11px; color: #888; font-weight: normal; margin-top: 4px;">
                    Generated on: {{ now()->format('Y-m-d H:i') }} | User: {{ auth()->user()->name }}
                </p>
            </div>

            <!-- Stats Overview -->
            <div class="stat-grid-4">
                <div class="stat-box">
                    <span>Total Candidates</span>
                    <strong>{{ $total }}</strong>
                </div>
                <div class="stat-box">
                    <span>Approved (Pass)</span>
                    <strong>{{ $approved }}</strong>
                </div>
                <div class="stat-box">
                    <span>Rejected (Fail)</span>
                    <strong>{{ $rejected }}</strong>
                </div>
                <div class="stat-box" style="background-color: #fdf6f6; border-color: #ebdbe0;">
                    <span>Total Credits Awarded</span>
                    <strong style="color: #6e1730;">{{ $totalCreditHours }}</strong>
                </div>
            </div>

            <h3>APEL.C Credit Transfer Candidate Queue</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Candidate Name</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Credits</th>
                        <th>Evaluator Status</th>
                        <th>Final Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        @php
                            $subjectCredits = \App\Http\Controllers\Admin\ApplicationManagementController::getCreditHoursFromCourseCode($app->credit_course_code);
                            $submission = $submissions[(string) $app->_id] ?? null;
                        @endphp
                        <tr>
                            <td style="font-weight: 600;">
                                {{ $names[(string) $app->user_id] ?? 'Unknown' }}
                            </td>
                            <td>{{ $app->credit_course_code }}</td>
                            <td>{{ $app->credit_course_name }}</td>
                            <td style="font-weight: 700; text-align: center;">{{ $subjectCredits }}</td>
                            <td>
                                @if($submission && $submission->graded_at)
                                    Score: {{ $submission->score }}% ({{ ucfirst($submission->result) }})
                                @else
                                    Awaiting Grading
                                @endif
                            </td>
                            <td>
                                @if($app->status === 'Final Approved')
                                    <span style="color: #146b45; font-weight: 700;">Approved (Pass)</span>
                                @elseif($app->status === 'Final Rejected')
                                    <span style="color: #a32a20; font-weight: 700;">Rejected (Fail)</span>
                                @else
                                    <span style="color: #f59e0b; font-weight: 700;">{{ $app->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888; padding: 20px;">
                                No APEL.C applications logged.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 50px; display: flex; justify-content: space-between; font-size: 12px;">
                <div>
                    <p style="margin-bottom: 40px;">Prepared by:</p>
                    <p style="border-top: 1px solid #000; width: 200px; padding-top: 5px; font-weight: bold;">
                        {{ auth()->user()->name }}
                    </p>
                    <p style="color: #555;">APEL Coordinator, UTM</p>
                </div>
                <div>
                    <p style="margin-bottom: 40px;">Approved by:</p>
                    <p style="border-top: 1px solid #000; width: 200px; padding-top: 5px; font-weight: bold;">
                        &nbsp;
                    </p>
                    <p style="color: #555;">Dean / Faculty Academic Office</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
