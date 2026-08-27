<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEL A Evaluator Brief - {{ $student->name ?? 'Student' }}</title>
    <style>
        body {
            margin: 0;
            background: var(--surface-sunk);
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 24px;
            background: #ffffff;
            border-bottom: 1px solid var(--line-strong);
        }

        .toolbar-title {
            font-weight: 700;
            color: #7f1d1d;
        }

        .btn {
            display: inline-block;
            padding: 7px 14px;
            border: 1px solid var(--line-strong);
            border-radius: 6px;
            color: var(--ink-2);
            background: #ffffff;
            font-weight: 700;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--maroon);
            border-color: var(--maroon);
            color: #ffffff;
        }

        .paper {
            width: 210mm;
            min-height: 297mm;
            margin: 28px auto;
            padding: 18mm;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
            box-sizing: border-box;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 3px solid var(--ink);
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .header p {
            margin: 0;
            color: var(--ink-2);
        }

        .score-box {
            min-width: 150px;
            text-align: right;
        }

        .score {
            display: block;
            font-size: 38px;
            line-height: 1;
            font-weight: 800;
            color: #065f46;
        }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-low {
            color: #065f46;
            background: #d1fae5;
        }

        .badge-medium {
            color: var(--attention);
            background: var(--attention-tint);
        }

        .badge-high {
            color: #991b1b;
            background: #fee2e2;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .card {
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            padding: 12px;
            page-break-inside: avoid;
        }

        .card h2 {
            margin: 0 0 8px 0;
            font-size: 14px;
            text-transform: uppercase;
            color: var(--ink);
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .meta-table td,
        .meta-table th {
            border: 1px solid var(--line-strong);
            padding: 7px;
            text-align: left;
            vertical-align: top;
        }

        .meta-table th {
            width: 165px;
            background: var(--surface-sunk);
            color: var(--ink-2);
        }

        .section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .section h2 {
            margin: 0 0 8px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--ink);
            font-size: 15px;
            text-transform: uppercase;
        }

        .list {
            margin: 0;
            padding-left: 18px;
        }

        .list li {
            margin-bottom: 6px;
        }

        .criteria-table {
            width: 100%;
            border-collapse: collapse;
        }

        .criteria-table th,
        .criteria-table td {
            border: 1px solid var(--line-strong);
            padding: 7px;
            vertical-align: top;
            text-align: left;
        }

        .criteria-table th {
            background: var(--surface-sunk);
        }

        .status-pass {
            color: #047857;
            font-weight: 800;
        }

        .status-warning {
            color: var(--attention);
            font-weight: 800;
        }

        .status-fail {
            color: var(--bad);
            font-weight: 800;
        }

        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid var(--line-strong);
            color: var(--ink-3);
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .paper {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar-title">APEL A Evaluator Brief</div>
        <div>
            <a href="{{ route('admin.applications.assign.form', $application->_id) }}" class="btn">Back</a>
            <button onclick="window.print()" class="btn btn-primary">Print Brief</button>
        </div>
    </div>

    @php
        $eligibility = $brief['eligibility'];
        $classification = $brief['classification'];
        $classificationClass = match ($classification['level']) {
            'low' => 'badge-low',
            'medium' => 'badge-medium',
            default => 'badge-high',
        };
    @endphp

    <main class="paper">
        <section class="header">
            <div>
                <h1>APEL A Evaluator Brief</h1>
                <p>Generated decision-support summary for evaluator review.</p>
                <p>Generated at: {{ $brief['generated_at']->format('Y-m-d H:i') }}</p>
            </div>
            <div class="score-box">
                <span class="score">{{ $eligibility['score'] }}%</span>
                <span class="badge {{ $classificationClass }}">{{ $classification['label'] }}</span>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Applicant</h2>
                <table class="meta-table">
                    <tr>
                        <th>Name</th>
                        <td>{{ $student->name ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <th>Programme</th>
                        <td>{{ $application->program_applied }}</td>
                    </tr>
                    <tr>
                        <th>Submission Date</th>
                        <td>{{ $application->submission_date }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>{{ $application->status }}</td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>System Recommendation</h2>
                <p><strong>{{ $eligibility['recommendation'] }}</strong></p>
                <p>{{ $eligibility['summary'] }}</p>
                <p><strong>Classification reason:</strong> {{ $classification['reason'] }}</p>
            </div>
        </section>

        <section class="section">
            <h2>Evidence Gap Analyzer</h2>
            @if ($brief['evidence_gaps']->count() > 0)
                <table class="criteria-table">
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Severity</th>
                            <th>Current Value</th>
                            <th>Evaluator Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($brief['evidence_gaps'] as $gap)
                            <tr>
                                <td>{{ $gap['area'] }}</td>
                                <td>{{ ucfirst($gap['severity']) }}</td>
                                <td>{{ $gap['value'] }}</td>
                                <td>{{ $gap['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No evidence gaps detected by the current rule set.</p>
            @endif
        </section>

        <section class="section">
            <h2>Evaluator Focus Points</h2>
            <ul class="list">
                @foreach ($brief['focus_areas'] as $focus)
                    <li><strong>{{ $focus['title'] }}:</strong> {{ $focus['detail'] }}</li>
                @endforeach
            </ul>
        </section>

        <section class="section">
            <h2>Eligibility Criteria Breakdown</h2>
            <table class="criteria-table">
                <thead>
                    <tr>
                        <th>Criteria</th>
                        <th>Status</th>
                        <th>Points</th>
                        <th>Value</th>
                        <th>Explanation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($eligibility['criteria'] as $criterion)
                        <tr>
                            <td>{{ $criterion['name'] }}</td>
                            <td class="status-{{ $criterion['status'] }}">{{ ucfirst($criterion['status']) }}</td>
                            <td>{{ $criterion['points'] }}/{{ $criterion['max_points'] }}</td>
                            <td>{{ $criterion['value'] }}</td>
                            <td>{{ $criterion['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2>Suggested Next Actions</h2>
            <ul class="list">
                @foreach ($brief['next_actions'] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ul>
        </section>

        <section class="section">
            <h2>Efficiency Contribution</h2>
            <ul class="list">
                @foreach ($brief['efficiency_notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </section>

        <div class="footer">
            <span>UTM APEL Management System - Decision Support Brief</span>
            <span>Application ID: {{ $application->_id }}</span>
        </div>
    </main>
</body>
</html>
