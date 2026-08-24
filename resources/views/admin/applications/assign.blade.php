@extends('layouts.app')

@section('content')
    <div class="container admin-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">
                    {{ $application->application_type === 'APEL A' ? 'APEL A Assignment' : 'APEL C Assignment' }}
                </span>
                <h2>
                    {{ $application->application_type === 'APEL A' ? 'Manage APEL A Review Assignment' : 'Assign Evaluator for APEL C' }}
                </h2>
                <p class="muted page-hero-text">
                    @if ($application->application_type === 'APEL A')
                        Assign an evaluator to review the admission application and provide an admission recommendation.
                    @else
                        Assign an evaluator to continue the assessment paper and grading workflow for this APEL C
                        application.
                    @endif
                </p>
            </div>

            <div class="hero-actions" style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('student.applications.print', $application->_id) }}" target="_blank" class="btn">🖨️ Export PDF Report</a>
                @if ($application->application_type === 'APEL A')
                    <a href="{{ route('admin.applications.brief', $application->_id) }}" target="_blank" class="btn">
                        Evaluator Brief
                    </a>
                @endif
                <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">Back to Applications</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success" style="margin-bottom: 20px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="review-layout">
            <div class="review-main">
                <section class="record-card">
                    <div class="record-top">
                        <div>
                            <p class="record-kicker">{{ $application->application_type }}</p>
                            <h3>{{ $application->program_applied }}</h3>
                        </div>

                        <div class="record-top-right">
                            @if ($application->status == 'pending')
                                <span class="badge badge-pending">Pending</span>
                            @elseif ($application->status == 'approved')
                                <span class="badge badge-approved">Approved</span>
                            @elseif ($application->status == 'rejected')
                                <span class="badge badge-rejected">Rejected</span>
                            @endif
                        </div>
                    </div>

                    <div class="record-meta-grid">
                        <div class="meta-box">
                            <span class="meta-label">Student Name</span>
                            <strong>{{ \App\Models\User::where('_id', $application->user_id)->value('name') ?? 'Unknown' }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Submission Date</span>
                            <strong>{{ $application->submission_date }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Current Evaluator</span>
                            <strong>{{ \App\Models\User::where('_id', $application->evaluator_id)->value('name') ?? 'Not Assigned' }}</strong>
                        </div>
                    </div>

                    <div class="record-meta-grid">
                        <div class="meta-box">
                            <span class="meta-label">Application Type</span>
                            <strong>{{ $application->application_type }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Workflow Stage</span>
                            <strong>
                                @if (in_array($application->status ?? '', ['Final Approved', 'Final Rejected']))
                                    Completed
                                @elseif ($application->application_type === 'APEL A')
                                    {{ ucfirst(str_replace('_', ' ', $application->review_stage ?? 'submitted')) }}
                                @else
                                    {{ $application->evaluator_id ? 'Assessment flow in progress' : 'Waiting for evaluator assignment' }}
                                @endif
                            </strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Admission Decision</span>
                            <strong>
                                @if ($application->application_type === 'APEL A')
                                    {{ ucfirst(str_replace('_', ' ', $application->admission_decision ?? 'pending')) }}
                                @else
                                    Not applicable
                                @endif
                            </strong>
                        </div>
                    </div>

                    @if (($apelAEligibility ?? null) && $application->application_type === 'APEL A')
                        <div class="record-panel" style="margin-top: 18px; border: 1px solid #d1fae5; background: #f0fdf4;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; flex-wrap: wrap;">
                                <div>
                                    <h4 style="margin-bottom: 6px;">APEL A Eligibility Decision Support</h4>
                                    <p class="feedback-text" style="margin-bottom: 0;">
                                        {{ $apelAEligibility['summary'] }}
                                    </p>
                                </div>

                                <div style="min-width: 180px;">
                                    <span class="meta-label">Readiness Score</span>
                                    <strong style="display: block; font-size: 34px; line-height: 1; color: #065f46;">
                                        {{ $apelAEligibility['score'] }}%
                                    </strong>
                                    <span class="badge badge-approved" style="margin-top: 8px; display: inline-flex;">
                                        {{ $apelAEligibility['recommendation'] }}
                                    </span>
                                </div>
                            </div>

                            <div style="height: 10px; background: #d1fae5; border-radius: 999px; overflow: hidden; margin: 18px 0;">
                                <div style="height: 100%; width: {{ $apelAEligibility['score'] }}%; background: #10b981;"></div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                                @foreach ($apelAEligibility['criteria'] as $criterion)
                                    @php
                                        $criterionColor = match ($criterion['status']) {
                                            'pass' => '#059669',
                                            'warning' => '#b45309',
                                            default => '#dc2626',
                                        };
                                    @endphp

                                    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px;">
                                        <div style="display: flex; justify-content: space-between; gap: 10px; align-items: flex-start;">
                                            <strong style="font-size: 13.5px; color: #1f2937;">{{ $criterion['name'] }}</strong>
                                            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ $criterionColor }};">
                                                {{ $criterion['status'] }}
                                            </span>
                                        </div>
                                        <div style="font-size: 12.5px; color: #4b5563; margin-top: 6px;">
                                            <strong>Value:</strong> {{ $criterion['value'] }}
                                        </div>
                                        <div style="font-size: 12.5px; color: #4b5563; margin-top: 4px;">
                                            {{ $criterion['message'] }}
                                        </div>
                                        <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                                            {{ $criterion['points'] }}/{{ $criterion['max_points'] }} points
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (($evaluatorBrief ?? null) && $evaluatorBrief['evidence_gaps']->count() > 0)
                                <div style="margin-top: 18px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: flex-start;">
                                        <div>
                                            <h4 style="margin-bottom: 6px;">Evidence Gap Analyzer</h4>
                                            <p class="feedback-text" style="margin-bottom: 0;">
                                                The system highlights weak or missing evidence so evaluators can review faster.
                                            </p>
                                        </div>
                                        <span class="badge badge-pending">
                                            {{ $evaluatorBrief['classification']['label'] }}
                                        </span>
                                    </div>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-top: 12px;">
                                        @foreach ($evaluatorBrief['evidence_gaps'] as $gap)
                                            <div style="border: 1px solid #f3f4f6; border-radius: 8px; padding: 10px; background: #f9fafb;">
                                                <strong style="font-size: 13px; color: #1f2937;">{{ $gap['area'] }}</strong>
                                                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                                    Severity: {{ ucfirst($gap['severity']) }}
                                                </div>
                                                <div style="font-size: 12.5px; color: #4b5563; margin-top: 6px;">
                                                    {{ $gap['message'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif (($evaluatorBrief ?? null) && $application->application_type === 'APEL A')
                                <div style="margin-top: 18px; background: #ffffff; border: 1px solid #d1fae5; border-radius: 8px; padding: 14px;">
                                    <h4 style="margin-bottom: 6px;">Evidence Gap Analyzer</h4>
                                    <p class="feedback-text" style="margin-bottom: 0;">
                                        No evidence gaps detected. The application can be reviewed using the generated evaluator brief.
                                    </p>
                                </div>
                            @endif

                            @if (($evaluatorBrief ?? null) && $application->application_type === 'APEL A')
                                <div style="margin-top: 14px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px;">
                                    <h4 style="margin-bottom: 8px;">Evaluator Focus Points</h4>
                                    <ul style="margin: 0; padding-left: 18px; color: #4b5563; font-size: 13px;">
                                        @foreach ($evaluatorBrief['focus_areas'] as $focus)
                                            <li style="margin-bottom: 6px;">
                                                <strong>{{ $focus['title'] }}:</strong> {{ $focus['detail'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="record-panel">
                        <h4>Internal Application Form Details</h4>

                        @if (($application->appeal_status ?? null) === 'submitted')
                            <section class="record-card" style="margin-top: 24px; border: 2px solid #f59e0b;">
                                <div class="record-top">
                                    <div>
                                        <p class="record-kicker">APPEAL REQUEST</p>
                                        <h3>Student Appeal Submission</h3>
                                    </div>

                                    <div>
                                        <span class="badge badge-pending">
                                            Appeal Submitted
                                        </span>
                                    </div>
                                </div>

                                <div class="record-panel">
                                    <p class="feedback-text">
                                        The student has submitted an appeal for re-evaluation of this APEL C application.
                                    </p>
                                    @if($application->appeal_remarks)
                                        <p class="feedback-text" style="margin-top: 10px; background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 8px;">
                                            <strong>Student Appeal Reason:</strong><br>
                                            <em>{{ $application->appeal_remarks }}</em>
                                        </p>
                                    @endif
                                </div>

                                <form method="POST"
                                    action="{{ route('admin.applications.update_status', $application->_id) }}">

                                    @csrf

                                    <input type="hidden" name="status" value="Assessment In Progress">

                                    <button type="submit" class="btn">
                                        Reopen Assessment
                                    </button>
                                </form>
                            </section>
                        @endif

                        @if ($application->application_type === 'APEL A')
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 10px; margin-bottom: 15px; font-size: 13.5px; color: #4b5563;">
                                <div><strong>Age:</strong> {{ $application->age ?? 'Not provided' }}</div>
                                <div><strong>University:</strong> {{ $application->university_name ?? 'Not provided' }}</div>
                                <div><strong>Company:</strong> {{ $application->company_name ?? 'Not provided' }}</div>
                            </div>

                            <p class="feedback-text">
                                <strong>Highest Qualification:</strong>
                                {{ $application->highest_qualification ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Current Job:</strong>
                                {{ $application->current_job ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Working Experience:</strong>
                                {{ $application->working_experience_years ?? '0' }} year(s)
                            </p>

                            <p class="feedback-text">
                                <strong>Experience Details:</strong><br>
                                {{ $application->working_experience_details ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Reason for Applying:</strong><br>
                                {{ $application->reason_applying ?? 'Not provided' }}
                            </p>
                        @else
                            <p class="feedback-text">
                                <strong>Course:</strong>
                                {{ $application->credit_course_name ?? 'Not provided' }}
                                @if ($application->credit_course_code)
                                    ({{ $application->credit_course_code }})
                                @endif
                            </p>

                            <p class="feedback-text">
                                <strong>Prior Learning / Work Experience:</strong><br>
                                {{ $application->prior_learning_experience ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Self-Assessment Statement:</strong><br>
                                {{ $application->self_assessment_statement ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Evidence Description:</strong><br>
                                {{ $application->evidence_description ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Portfolio Summary:</strong><br>
                                {{ $application->portfolio_summary ?? 'Not provided' }}
                            </p>

                            <p class="feedback-text">
                                <strong>Evidence File(s):</strong><br>

                                @if (!empty($application->evidence_file))
                                    @foreach ($application->evidence_file as $file)
                                        @php
                                            $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                            $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                                        @endphp
                                        <a href="{{ asset('storage/' . $filePath) }}" target="_blank">
                                            {{ $fileName }}
                                        </a><br>
                                    @endforeach
                                @else
                                    No evidence file uploaded.
                                @endif
                            </p>

                            <p class="feedback-text">
                                <strong>Portfolio File(s):</strong><br>

                                @if (!empty($application->portfolio_file))
                                    @foreach ($application->portfolio_file as $file)
                                        @php
                                            $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                            $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                                        @endphp
                                        <a href="{{ asset('storage/' . $filePath) }}" target="_blank">
                                            {{ $fileName }}
                                        </a><br>
                                    @endforeach
                                @else
                                    No portfolio file uploaded.
                                @endif
                            </p>

                            @if ($application->application_type === 'APEL C')
                                <!-- Dynamic Style Overrides -->
                                <style>
                                    .cards-container {
                                        display: flex;
                                        flex-direction: column;
                                        gap: 16px;
                                        margin-bottom: 20px;
                                    }
                                    .row-card {
                                        background: #ffffff;
                                        border: 1px solid #e5e7eb;
                                        border-radius: 12px;
                                        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                                        overflow: hidden;
                                    }
                                    .row-card-header {
                                        background: #fafafb;
                                        border-bottom: 1px solid #e5e7eb;
                                        padding: 10px 16px;
                                        display: flex;
                                        justify-content: space-between;
                                        align-items: center;
                                        font-weight: 600;
                                        color: #8B1E3F;
                                    }
                                    .row-card-body {
                                        padding: 16px;
                                        display: grid;
                                        gap: 12px;
                                    }
                                    .row-card-body .field-col {
                                        display: flex;
                                        flex-direction: column;
                                        gap: 4px;
                                    }
                                    .row-card-body label {
                                        font-size: 11px;
                                        font-weight: 600;
                                        color: #4b5563;
                                        margin-bottom: 0 !important;
                                        text-transform: uppercase;
                                        letter-spacing: 0.05em;
                                    }
                                    .row-card-body strong {
                                        font-size: 13.5px;
                                        color: #1f2937;
                                    }
                                    .details-tab-content {
                                        animation: fadeIn 0.2s ease-in-out;
                                    }
                                    @keyframes fadeIn {
                                        from { opacity: 0; transform: translateY(4px); }
                                        to { opacity: 1; transform: translateY(0); }
                                    }
                                    .form-tabs .tab-link.active {
                                        color: #ffffff !important;
                                        background: #8B1E3F !important;
                                        border-radius: 6px;
                                    }
                                </style>

                                <!-- Submitted Pre-Application Details Card -->
                                <div style="margin-top: 24px; border-top: 1px dashed #e5e7eb; padding-top: 20px;">
                                    <h4 style="font-size: 14.5px; font-weight: 700; color: #8B1E3F; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Submitted Pre-Application Form Data</h4>

                                    <div class="form-tabs" style="margin-top: 10px; display: flex; gap: 6px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 15px;">
                                        <button type="button" class="tab-link active" onclick="openDetailsTab(event, 'details-particulars')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Personal Particulars</button>
                                        <button type="button" class="tab-link" onclick="openDetailsTab(event, 'details-education')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Formal Learning</button>
                                        <button type="button" class="tab-link" onclick="openDetailsTab(event, 'details-experience')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Experience & Training</button>
                                        <button type="button" class="tab-link" onclick="openDetailsTab(event, 'details-other-skills')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Other Learning</button>
                                    </div>

                                    <!-- TAB 1: Personal Particulars -->
                                    <div id="details-particulars" class="details-tab-content" style="display: block;">
                                        @php
                                            $personal = $application->pre_app_data['personal_particulars'] ?? [];
                                            $studentUser = \App\Models\User::find($application->user_id);
                                        @endphp
                                        <div class="record-meta-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 15px;">
                                            <div class="meta-box">
                                                <span class="meta-label">Full Name</span>
                                                <strong>{{ $personal['name'] ?? ($studentUser->name ?? 'N/A') }}</strong>
                                            </div>
                                            <div class="meta-box">
                                                <span class="meta-label">Matric No.</span>
                                                <strong>{{ $personal['matric_no'] ?? 'N/A' }}</strong>
                                            </div>
                                            <div class="meta-box">
                                                <span class="meta-label">Identity Card No.</span>
                                                <strong>{{ $personal['ic_no'] ?? 'N/A' }}</strong>
                                            </div>
                                            <div class="meta-box">
                                                <span class="meta-label">Highest Academic Qualification</span>
                                                <strong>{{ $personal['highest_qualification'] ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 2: Formal Learning -->
                                    <div id="details-education" class="details-tab-content" style="display: none;">
                                        @php
                                            $formal = $application->pre_app_data['formal_learning'] ?? [];
                                        @endphp
                                        @if(!empty($formal) && count($formal) > 0 && (!empty($formal[0]['title_of_certification']) || !empty($formal[0]['awarding_body'])))
                                            <div class="cards-container">
                                                @foreach ($formal as $idx => $item)
                                                    @if(!empty($item['title_of_certification']) || !empty($item['awarding_body']))
                                                        <div class="row-card">
                                                            <div class="row-card-header">
                                                                <span>Education Entry #{{ $idx + 1 }}</span>
                                                            </div>
                                                            <div class="row-card-body education-grid" style="grid-template-columns: repeat(12, 1fr); gap: 12px;">
                                                                <div class="field-col" style="grid-column: span 3;">
                                                                    <label>Year Awarded</label>
                                                                    <strong>{{ $item['year_awarded'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col" style="grid-column: span 9;">
                                                                    <label>Title of Certification</label>
                                                                    <strong>{{ $item['title_of_certification'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col" style="grid-column: span 6;">
                                                                    <label>Level of Award</label>
                                                                    <strong>{{ $item['level_of_award'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col" style="grid-column: span 6;">
                                                                    <label>Awarding Body</label>
                                                                    <strong>{{ $item['awarding_body'] ?? 'N/A' }}</strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13px;">No formal learning recorded.</p>
                                        @endif
                                    </div>

                                    <!-- TAB 3: Experience & Training -->
                                    <div id="details-experience" class="details-tab-content" style="display: none;">
                                        @php
                                            $jobs = $application->pre_app_data['experiential_learning'] ?? [];
                                            $trainings = $application->pre_app_data['training_activities'] ?? [];
                                            $skillsList = [
                                                "Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills",
                                                "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility",
                                                "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"
                                            ];
                                        @endphp
                                        
                                        <h5 style="color: #8B1E3F; margin-bottom: 12px; font-size: 13.5px; font-weight: bold; border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px;">Experiential Learning (Employment History)</h5>
                                        @if(!empty($jobs) && count($jobs) > 0 && (!empty($jobs[0]['employer_name']) || !empty($jobs[0]['position_held'])))
                                            <div class="cards-container" style="margin-bottom: 25px;">
                                                @foreach ($jobs as $idx => $item)
                                                    @if(!empty($item['employer_name']) || !empty($item['position_held']))
                                                        <div class="row-card">
                                                            <div class="row-card-header">
                                                                <span>Employer Entry #{{ $idx + 1 }}</span>
                                                            </div>
                                                            <div class="row-card-body employment-grid" style="grid-template-columns: repeat(12, 1fr); gap: 12px;">
                                                                <div class="field-col employer-name" style="grid-column: span 6;">
                                                                    <label>Employer Name</label>
                                                                    <strong>{{ $item['employer_name'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col contact-address" style="grid-column: span 6;">
                                                                    <label>Contact Address</label>
                                                                    <strong>{{ $item['contact_address'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col time-from" style="grid-column: span 3;">
                                                                    <label>From (Month/Year)</label>
                                                                    <strong>{{ $item['time_from'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col time-to" style="grid-column: span 3;">
                                                                    <label>To (Month/Year)</label>
                                                                    <strong>{{ $item['time_to'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col position-held" style="grid-column: span 6;">
                                                                    <label>Position Held</label>
                                                                    <strong>{{ $item['position_held'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col job-roles" style="grid-column: span 12;">
                                                                    <label>Job Roles / Performed</label>
                                                                    <p style="margin: 4px 0 0 0; color: #374151; font-size: 12.5px; line-height: 1.5; white-space: pre-wrap;">{{ $item['job_roles'] ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13px; margin-bottom: 25px;">No employment history recorded.</p>
                                        @endif

                                        <h5 style="color: #8B1E3F; margin-bottom: 12px; font-size: 13.5px; font-weight: bold; border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px;">Training Activities</h5>
                                        @if(!empty($trainings) && count($trainings) > 0 && (!empty($trainings[0]['course_name']) || !empty($trainings[0]['location'])))
                                            <div class="cards-container">
                                                @foreach ($trainings as $idx => $item)
                                                    @if(!empty($item['course_name']) || !empty($item['location']))
                                                        <div class="row-card">
                                                            <div class="row-card-header">
                                                                <span>Training Entry #{{ $idx + 1 }}</span>
                                                            </div>
                                                            <div class="row-card-body training-grid" style="grid-template-columns: repeat(12, 1fr); gap: 12px;">
                                                                <div class="field-col course-name" style="grid-column: span 6;">
                                                                    <label>Course/Training Name</label>
                                                                    <strong>{{ $item['course_name'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col location" style="grid-column: span 6;">
                                                                    <label>Location</label>
                                                                    <strong>{{ $item['location'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col date-duration" style="grid-column: span 6;">
                                                                    <label>Date & Duration</label>
                                                                    <strong>{{ $item['date_duration'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col activity-type" style="grid-column: span 6;">
                                                                    <label>Activity Type</label>
                                                                    <strong>{{ $item['activity_type'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col skills-learnt" style="grid-column: span 12;">
                                                                    <label>Skills Checklist / Learnt</label>
                                                                    <div class="skills-grid-view" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; background: #f9fafb; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 4px;">
                                                                        @php
                                                                            $checkedSkills = $item['skills_learnt'] ?? [];
                                                                        @endphp
                                                                        @foreach ($skillsList as $sIdx => $sName)
                                                                            <div style="font-size: 12px; color: {{ in_array($sIdx + 1, $checkedSkills) ? '#111827' : '#9ca3af' }}; display: flex; align-items: center; gap: 6px;">
                                                                                <span style="font-size: 13px;">{{ in_array($sIdx + 1, $checkedSkills) ? '☑' : '☐' }}</span>
                                                                                <span>{{ $sIdx + 1 }}. {{ $sName }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13px;">No training activities recorded.</p>
                                        @endif
                                    </div>

                                    <!-- TAB 4: Other Learning -->
                                    <div id="details-other-skills" class="details-tab-content" style="display: none;">
                                        @php
                                            $otherSkills = $application->pre_app_data['other_learning_skills'] ?? [];
                                            $langSkills = $application->pre_app_data['language_skills'] ?? [];
                                            $skillsList = [
                                                "Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills",
                                                "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility",
                                                "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"
                                            ];
                                        @endphp

                                        <h5 style="color: #8B1E3F; margin-bottom: 12px; font-size: 13.5px; font-weight: bold; border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px;">Other Learning Skills / Activities</h5>
                                        @if(!empty($otherSkills) && count($otherSkills) > 0 && (!empty($otherSkills[0]['other_activities']) || !empty($otherSkills[0]['year'])))
                                            <div class="cards-container" style="margin-bottom: 25px;">
                                                @foreach ($otherSkills as $idx => $item)
                                                    @if(!empty($item['other_activities']) || !empty($item['year']))
                                                        <div class="row-card">
                                                            <div class="row-card-header">
                                                                <span>Other Activity Entry #{{ $idx + 1 }}</span>
                                                            </div>
                                                            <div class="row-card-body other-skills-grid" style="grid-template-columns: repeat(12, 1fr); gap: 12px;">
                                                                <div class="field-col other-activities" style="grid-column: span 9;">
                                                                    <label>Description</label>
                                                                    <strong>{{ $item['other_activities'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col year" style="grid-column: span 3;">
                                                                    <label>Year</label>
                                                                    <strong>{{ $item['year'] ?? 'N/A' }}</strong>
                                                                </div>
                                                                <div class="field-col skills-learnt" style="grid-column: span 12;">
                                                                    <label>Skills Checklist / Learnt</label>
                                                                    <div class="skills-grid-view" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; background: #f9fafb; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 4px;">
                                                                        @php
                                                                            $checkedSkills = $item['skills_learnt'] ?? [];
                                                                        @endphp
                                                                        @foreach ($skillsList as $sIdx => $sName)
                                                                            <div style="font-size: 12px; color: {{ in_array($sIdx + 1, $checkedSkills) ? '#111827' : '#9ca3af' }}; display: flex; align-items: center; gap: 6px;">
                                                                                <span style="font-size: 13px;">{{ in_array($sIdx + 1, $checkedSkills) ? '☑' : '☐' }}</span>
                                                                                <span>{{ $sIdx + 1 }}. {{ $sName }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13px; margin-bottom: 25px;">No other learning activities recorded.</p>
                                        @endif

                                        <h5 style="color: #8B1E3F; margin-bottom: 12px; font-size: 13.5px; font-weight: bold; border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px;">Language Skills</h5>
                                        @if(!empty($langSkills))
                                            <table class="dynamic-table" style="width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 10px;">
                                                <thead>
                                                    <tr style="background: #fafafb; border-bottom: 1px solid #e5e7eb;">
                                                        <th style="padding: 10px; text-align: left; font-size: 12px; color: #4b5563; font-weight: 600;">Language</th>
                                                        <th style="padding: 10px; text-align: center; font-size: 12px; color: #4b5563; font-weight: 600;">Listening</th>
                                                        <th style="padding: 10px; text-align: center; font-size: 12px; color: #4b5563; font-weight: 600;">Reading</th>
                                                        <th style="padding: 10px; text-align: center; font-size: 12px; color: #4b5563; font-weight: 600;">Speaking</th>
                                                        <th style="padding: 10px; text-align: center; font-size: 12px; color: #4b5563; font-weight: 600;">Writing</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($langSkills as $item)
                                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                                            <td style="padding: 10px; font-size: 13px; font-weight: 600; color: #1f2937;">{{ $item['language'] ?? 'N/A' }}</td>
                                                            <td style="padding: 10px; text-align: center; font-size: 13px; color: #374151;">{{ $item['listening'] ?? '3' }} / 4</td>
                                                            <td style="padding: 10px; text-align: center; font-size: 13px; color: #374151;">{{ $item['reading'] ?? '3' }} / 4</td>
                                                            <td style="padding: 10px; text-align: center; font-size: 13px; color: #374151;">{{ $item['speaking'] ?? '3' }} / 4</td>
                                                            <td style="padding: 10px; text-align: center; font-size: 13px; color: #374151;">{{ $item['writing'] ?? '3' }} / 4</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13px;">No language skills recorded.</p>
                                        @endif
                                    </div>
                                </div>

                                <script>
                                    function openDetailsTab(evt, tabId) {
                                        const contents = document.getElementsByClassName("details-tab-content");
                                        for (let i = 0; i < contents.length; i++) {
                                            contents[i].style.display = "none";
                                        }
                                        
                                        const links = evt.currentTarget.parentElement.querySelectorAll(".tab-link");
                                        links.forEach(link => {
                                            link.classList.remove("active");
                                            link.style.color = "#6b7280";
                                            link.style.background = "transparent";
                                        });
                                        
                                        document.getElementById(tabId).style.display = "block";
                                        evt.currentTarget.classList.add("active");
                                    }

                                    document.addEventListener("DOMContentLoaded", function() {
                                        const activeLink = document.querySelector(".form-tabs .tab-link.active");
                                        if (activeLink) {
                                            activeLink.classList.add("active");
                                        }
                                    });
                                </script>
                            @endif
                        @endif
                    </div>

                    <div class="record-panel">
                        <h4>Evaluator Feedback</h4>
                        @php
                            $eval1 = $application->evaluator_id ? \App\Models\User::where('_id', $application->evaluator_id)->value('name') : null;
                            $eval2 = $application->evaluator_2_id ? \App\Models\User::where('_id', $application->evaluator_2_id)->value('name') : null;

                            $hasFeedback = false;
                        @endphp

                        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
                            @if ($application->application_type === 'APEL A')
                                @if ($eval1)
                                    <div style="background: #fdfbfb; padding: 10px 12px; border-radius: 8px; border: 1px solid #f3ebee;">
                                        <strong>{{ $eval1 }} (First Reviewer):</strong>
                                        <div style="margin-top: 5px; font-size: 13px; color: #4b5563;">
                                            @if ($application->evaluator_1_reviewed_at)
                                                <div>Recommendation: <strong style="color: {{ $application->evaluator_1_decision === 'recommended' ? '#10b981' : '#ef4444' }};">{{ ucfirst($application->evaluator_1_decision) }}</strong></div>
                                                <div style="margin-top: 4px; font-style: italic;">"{{ $application->evaluator_1_feedback ?? 'No feedback text provided' }}"</div>
                                                @php $hasFeedback = true; @endphp
                                            @else
                                                <span style="color: #6b7280; font-style: italic;">No feedback submitted yet.</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($eval2)
                                    <div style="background: #fdfbfb; padding: 10px 12px; border-radius: 8px; border: 1px solid #f3ebee;">
                                        <strong>{{ $eval2 }} (Second Reviewer):</strong>
                                        <div style="margin-top: 5px; font-size: 13px; color: #4b5563;">
                                            @if ($application->evaluator_2_reviewed_at)
                                                <div>Recommendation: <strong style="color: {{ $application->evaluator_2_decision === 'recommended' ? '#10b981' : '#ef4444' }};">{{ ucfirst($application->evaluator_2_decision) }}</strong></div>
                                                <div style="margin-top: 4px; font-style: italic;">"{{ $application->evaluator_2_feedback ?? 'No feedback text provided' }}"</div>
                                                @php $hasFeedback = true; @endphp
                                            @else
                                                <span style="color: #6b7280; font-style: italic;">No feedback submitted yet.</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @else
                                {{-- APEL C --}}
                                @php
                                    $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();
                                @endphp
                                @if ($eval1)
                                    <div style="background: #fdfbfb; padding: 10px 12px; border-radius: 8px; border: 1px solid #f3ebee;">
                                        <strong>{{ $eval1 }} (First Evaluator):</strong>
                                        <div style="margin-top: 5px; font-size: 13px; color: #4b5563;">
                                            @if ($submission && $submission->evaluator_1_graded_at)
                                                @if (($application->assessment_type ?? '') === 'portfolio')
                                                    <div>Result: <strong style="color: {{ $submission->evaluator_1_result === 'pass' ? '#10b981' : '#ef4444' }};">{{ $submission->evaluator_1_result === 'pass' ? 'Approved' : 'Rejected' }}</strong></div>
                                                @else
                                                    <div>Score: <strong>{{ $submission->evaluator_1_score }}%</strong> | Result: <strong style="color: {{ $submission->evaluator_1_result === 'pass' ? '#10b981' : '#ef4444' }};">{{ ucfirst($submission->evaluator_1_result) }}</strong></div>
                                                @endif
                                                @if(isset($submission->evaluator_1_clo1))
                                                    <div style="font-size: 11.5px; color: #4b5563; margin-top: 2px;">
                                                        CLO Scores: CLO1: <strong>{{ $submission->evaluator_1_clo1 }}/10</strong> | CLO2: <strong>{{ $submission->evaluator_1_clo2 }}/10</strong> | CLO3: <strong>{{ $submission->evaluator_1_clo3 }}/10</strong> | CLO4: <strong>{{ $submission->evaluator_1_clo4 }}/10</strong>
                                                    </div>
                                                @endif
                                                <div style="margin-top: 4px; font-style: italic;">"{{ $submission->evaluator_1_feedback ?? 'No feedback text provided' }}"</div>
                                                @php $hasFeedback = true; @endphp
                                            @else
                                                <span style="color: #6b7280; font-style: italic;">No feedback submitted yet.</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($eval2)
                                    <div style="background: #fdfbfb; padding: 10px 12px; border-radius: 8px; border: 1px solid #f3ebee;">
                                        <strong>{{ $eval2 }} (Second Evaluator):</strong>
                                        <div style="margin-top: 5px; font-size: 13px; color: #4b5563;">
                                            @if ($submission && $submission->evaluator_2_graded_at)
                                                @if (($application->assessment_type ?? '') === 'portfolio')
                                                    <div>Result: <strong style="color: {{ $submission->evaluator_2_result === 'pass' ? '#10b981' : '#ef4444' }};">{{ $submission->evaluator_2_result === 'pass' ? 'Approved' : 'Rejected' }}</strong></div>
                                                @else
                                                    <div>Score: <strong>{{ $submission->evaluator_2_score }}%</strong> | Result: <strong style="color: {{ $submission->evaluator_2_result === 'pass' ? '#10b981' : '#ef4444' }};">{{ ucfirst($submission->evaluator_2_result) }}</strong></div>
                                                @endif
                                                @if(isset($submission->evaluator_2_clo1))
                                                    <div style="font-size: 11.5px; color: #4b5563; margin-top: 2px;">
                                                        CLO Scores: CLO1: <strong>{{ $submission->evaluator_2_clo1 }}/10</strong> | CLO2: <strong>{{ $submission->evaluator_2_clo2 }}/10</strong> | CLO3: <strong>{{ $submission->evaluator_2_clo3 }}/10</strong> | CLO4: <strong>{{ $submission->evaluator_2_clo4 }}/10</strong>
                                                    </div>
                                                @endif
                                                <div style="margin-top: 4px; font-style: italic;">"{{ $submission->evaluator_2_feedback ?? 'No feedback text provided' }}"</div>
                                                @php $hasFeedback = true; @endphp
                                            @else
                                                <span style="color: #6b7280; font-style: italic;">No feedback submitted yet.</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if (!$hasFeedback)
                                <p class="feedback-text" style="color: #6b7280; font-style: italic; margin: 0;">No evaluator feedback has been added yet.</p>
                            @endif
                        </div>
                    </div>
                </section>
            </div>

            <aside class="review-side">
                @if ($application->application_type === 'APEL C' && in_array($application->status ?? 'Pre-Application Submitted', ['Pre-Application Submitted', 'Under Advisor Review']))
                    {{-- ADVISOR REVIEW FORM --}}
                    <section class="card form-main-card" style="margin-bottom:20px;">
                        <h3 class="side-form-title">APEL C Advisor Recommendation</h3>
                        <form method="POST" action="{{ route('admin.applications.advisor_approve', $application->_id) }}">
                            @csrf
                            
                            <label>Advisor Name</label>
                            <select name="advisor_name" required>
                                <option value="">-- Select Advisor --</option>
                                <option value="Ts Dr. Maheyzah Md Siraj">Ts Dr. Maheyzah Md Siraj</option>
                                <option value="Dr. Hajar">Dr. Hajar</option>
                            </select>
                            
                            <label style="margin-top: 15px; display: block;">CLO Attainment Score (1-4)</label>
                            <p style="font-size: 11px; color: #5b626a; margin-top: 2px; margin-bottom: 8px;">Rate student's competence for each Course Learning Outcome:</p>
                            
                            <table class="dynamic-table" style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                                <thead>
                                    <tr>
                                        <th style="font-size: 11.5px; border: 1px solid #e5e7eb; padding: 6px; background: #f9fafb;">CLO</th>
                                        <th style="font-size: 11.5px; border: 1px solid #e5e7eb; padding: 6px; background: #f9fafb; width: 80px;">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['CLO1: Analyse governance & frameworks' => 'clo1', 'CLO2: Evaluate security applications' => 'clo2', 'CLO3: Complete risk lifecycle' => 'clo3', 'CLO4: Construct plans & tools' => 'clo4'] as $label => $key)
                                        <tr>
                                            <td style="font-size: 11px; border: 1px solid #e5e7eb; padding: 6px; line-height: 1.3;">{{ $label }}</td>
                                            <td style="border: 1px solid #e5e7eb; padding: 6px;">
                                                <select name="advisor_evaluation[{{ $key }}]" required class="clo-score" style="width: 100%; font-size: 11px; padding: 4px;">
                                                    <option value="4">4 - Excellent</option>
                                                    <option value="3">3 - Good</option>
                                                    <option value="2">2 - Fair</option>
                                                    <option value="1">1 - Poor</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            
                            <label>Recommendation</label>
                            <select name="recommendation_status" id="recommendation_status" required>
                                <option value="Recommended">Recommended (All CLOs >= 3)</option>
                                <option value="NOT recommended">NOT recommended</option>
                            </select>
                            
                            <label style="margin-top: 15px; display: block;">Recommended Mode of Assessment</label>
                            <select name="mode_of_assessment" required>
                                <option value="portfolio">Portfolio Submission</option>
                                <option value="test">Challenge Test</option>
                            </select>
                            
                            <label style="margin-top: 15px; display: block;">Advisor Remarks</label>
                            <textarea name="advisor_remarks" rows="3" placeholder="Write advisor evaluation comments..."></textarea>
                            
                            <div class="form-submit-row">
                                <button type="submit" class="btn">Submit Advisor Recommendation</button>
                            </div>
                        </form>
                    </section>
                @else
                    {{-- PAYMENT VERIFICATION --}}
                    <section class="card form-main-card" style="margin-bottom:20px;">
                        <h3 class="side-form-title">Payment Verification</h3>
                        <form method="POST" action="{{ route('admin.applications.update_payment', $application->_id) }}">
                            @csrf
                            <label>Payment Type</label>
                            <input type="text" value="{{ $application->payment_type ?? 'Application Fee' }}" readonly>

                        <select name="payment_status" required {{ ($application->payment_status ?? '') === 'verified' ? 'disabled' : '' }}>
                            <option value="pending"
                                {{ ($application->payment_status ?? 'pending') === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                            <option value="submitted"
                                {{ ($application->payment_status ?? '') === 'submitted' ? 'selected' : '' }}>
                                Submitted
                            </option>

                            <option value="verified"
                                {{ ($application->payment_status ?? '') === 'verified' ? 'selected' : '' }}>
                                Verified
                            </option>

                            <option value="rejected"
                                {{ ($application->payment_status ?? '') === 'rejected' ? 'selected' : '' }}>
                                Rejected
                            </option>
                        </select>

                        <label>Payment Receipt</label>

                        @if ($application->payment_receipt)
                            <p class="feedback-text">
                                <a href="{{ asset('storage/' . $application->payment_receipt) }}" target="_blank"
                                    class="link">
                                    View Uploaded Receipt
                                </a>
                            </p>
                        @else
                            <p class="feedback-text">No payment receipt uploaded yet.</p>
                        @endif

                        <label>Payment Remarks</label>
                        <textarea name="payment_remarks" rows="4" placeholder="Write payment verification remarks..." {{ ($application->payment_status ?? '') === 'verified' ? 'readonly' : '' }}>{{ $application->payment_remarks ?? '' }}</textarea>

                        @if (($application->payment_status ?? '') !== 'verified')
                            <div class="form-submit-row">
                                <button type="submit" class="btn">
                                    Update Payment
                                </button>
                            </div>
                        @else
                            <p class="feedback-text" style="color: #059669; font-weight: 600; margin-top: 15px; display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 16px;">✓</span> Payment Verified
                            </p>
                        @endif
                    </form>
                </section>

                {{-- ASSIGN EVALUATOR --}}
                <section class="card form-main-card">
                    <h3 class="side-form-title">
                        {{ $application->application_type === 'APEL A' ? 'Assign APEL A Reviewer' : 'Assign APEL C Evaluator' }}
                    </h3>

                    @if ($errors->any())
                        <div class="alert alert-error">
                            <ul style="padding-left: 18px;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $isAssigned = !empty($application->evaluator_id);
                    @endphp

                    @if ($isAssigned)
                        <div style="background-color: #ecfdf5; border: 1px solid #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; font-size: 13.5px; font-weight: 555; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                            <span>✓</span>
                            <span>Evaluators are already assigned. Use the dropdowns below to update/change the assignment.</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.applications.assign', $application->_id) }}">
                        @csrf

                        @php
                            $recommendations = $evaluatorRecommendations ?? collect();
                            $recommendationMap = $recommendations->keyBy('id');
                            $recommendedEvaluatorId = ($recommendations->first() ?? [])['id'] ?? null;
                        @endphp

                        @if ($recommendations->count() > 0)
                            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px; margin-bottom: 18px;">
                                <strong style="display: block; color: #1e3a8a; font-size: 13.5px; margin-bottom: 8px;">
                                    Smart Evaluator Recommendation
                                </strong>
                                <p class="feedback-text" style="margin-bottom: 12px;">
                                    Evaluators are ranked by active assignments, pending submissions, and average completion time.
                                </p>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px;">
                                    @foreach ($recommendations->take(3) as $index => $recommendation)
                                        <div style="background: #ffffff; border: 1px solid #dbeafe; border-radius: 8px; padding: 10px;">
                                            <div style="display: flex; justify-content: space-between; gap: 8px; align-items: center;">
                                                <strong style="font-size: 13.5px; color: #1f2937;">
                                                    {{ $recommendation['name'] }}
                                                </strong>
                                                @if ($index === 0)
                                                    <span style="font-size: 11px; color: #1d4ed8; font-weight: 700;">BEST FIT</span>
                                                @endif
                                            </div>
                                            <div style="font-size: 12.5px; color: #4b5563; margin-top: 6px;">
                                                Active: {{ $recommendation['active_assignments'] }} |
                                                Pending: {{ $recommendation['pending_submissions'] }}
                                            </div>
                                            <div style="font-size: 12.5px; color: #4b5563; margin-top: 4px;">
                                                Avg completion:
                                                {{ $recommendation['average_completion_days'] ?? 'N/A' }} day(s)
                                            </div>
                                            <div style="font-size: 12px; color: #6b7280; margin-top: 6px;">
                                                {{ $recommendation['recommendation_reason'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <label>Select First Evaluator</label>
                        <select name="evaluator_id" required>
                            <option value="">-- Select Evaluator 1 --</option>
                            @foreach ($evaluators as $evaluator)
                                @php
                                    $recommendation = $recommendationMap->get((string) $evaluator->_id);
                                    $selectFirst = (string) ($application->evaluator_id ?? '') === (string) $evaluator->_id
                                        || (!$isAssigned && (string) $recommendedEvaluatorId === (string) $evaluator->_id);
                                @endphp
                                <option value="{{ $evaluator->_id }}"
                                    {{ $selectFirst ? 'selected' : '' }}>
                                    {{ $evaluator->name }}
                                    @if ($recommendation)
                                        - active {{ $recommendation['active_assignments'] }}, pending {{ $recommendation['pending_submissions'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        <label style="margin-top: 15px; display: block;">Select Second Evaluator</label>
                        <select name="evaluator_2_id">
                            <option value="">-- Select Evaluator 2 (Optional) --</option>
                            @foreach ($evaluators as $evaluator)
                                @php
                                    $recommendation = $recommendationMap->get((string) $evaluator->_id);
                                    $selectSecond = (string) ($application->evaluator_2_id ?? '') === (string) $evaluator->_id;
                                @endphp
                                <option value="{{ $evaluator->_id }}"
                                    {{ $selectSecond ? 'selected' : '' }}>
                                    {{ $evaluator->name }}
                                    @if ($recommendation)
                                        - active {{ $recommendation['active_assignments'] }}, pending {{ $recommendation['pending_submissions'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @if ($application->application_type === 'APEL C')
                            <label style="margin-top: 15px; display: block;">Select Assessment Type</label>
                            <select name="assessment_type" required>
                                <option value="">-- Select Assessment Type --</option>
                                <option value="portfolio" {{ ($application->assessment_type ?? '') === 'portfolio' ? 'selected' : '' }}>
                                    Portfolio
                                </option>
                                <option value="test" {{ ($application->assessment_type ?? '') === 'test' ? 'selected' : '' }}>
                                    Test
                                </option>
                            </select>
                        @endif

                        <div class="tip-box tip-box-light">
                            <strong>Reminder</strong>
                            <p>
                                @if ($application->application_type === 'APEL A')
                                    This evaluator will review the admission application and provide the recommendation
                                    outcome.
                                @else
                                    This evaluator will continue the assessment paper upload and grading process for APEL C.
                                @endif
                            </p>
                        </div>

                        <div class="form-submit-row">
                            <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">Cancel</a>
                            @if (($application->payment_status ?? 'pending') === 'verified')
                                <button type="submit" class="btn">
                                    {{ $isAssigned ? 'Update Evaluator' : ($application->application_type === 'APEL A' ? 'Assign Reviewer' : 'Assign Evaluator') }}
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary" disabled>
                                    Verify Payment First
                                </button>
                            @endif
                        </div>
                    </form>
                </section>

                @if ($application->application_type === 'APEL A')
                    @php
                        $isSingleEvaluator = empty($application->evaluator_2_id);
                        $bothReviewedA = !empty($application->evaluator_1_reviewed_at) && !empty($application->evaluator_2_reviewed_at);
                        $canFinalizeA = $isSingleEvaluator ? !empty($application->evaluator_1_reviewed_at) : $bothReviewedA;
                    @endphp

                    <section class="card form-main-card" style="margin-top: 20px;">
                        <h3 class="side-form-title">Final Admin Decision</h3>

                        <form method="POST"
                            action="{{ route('admin.applications.finalize_apel_a', $application->_id) }}">
                            @csrf

                            <label>Final Decision</label>
                            <select name="final_decision" required {{ !$canFinalizeA || in_array($application->final_decision ?? '', ['approved', 'rejected']) ? 'disabled' : '' }}>
                                <option value="pending"
                                    {{ ($application->final_decision ?? 'pending') === 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="approved"
                                    {{ ($application->final_decision ?? '') === 'approved' ? 'selected' : '' }}>
                                    Approved
                                </option>
                                <option value="rejected"
                                    {{ ($application->final_decision ?? '') === 'rejected' ? 'selected' : '' }}>
                                    Rejected
                                </option>
                            </select>

                            <label>Final Decision Remarks</label>
                            <textarea name="final_decision_remarks" rows="6" placeholder="Write final admin remarks here..." {{ !$canFinalizeA || in_array($application->final_decision ?? '', ['approved', 'rejected']) ? 'readonly' : '' }}>{{ $application->final_decision_remarks }}</textarea>

                            <div class="tip-box tip-box-light">
                                <strong>Important</strong>
                                <p>
                                    This is the final APEL A admission outcome shown to the student after evaluator
                                    recommendations are submitted.
                                </p>
                            </div>

                            @if (!$canFinalizeA)
                                <div class="alert alert-warning" style="background-color: #fffbeb; border: 1px solid #fef3c7; color: #b45309; padding: 10px 14px; border-radius: 8px; margin-top: 15px; font-size: 13.5px; font-weight: 600; text-align: center;">
                                    ⚠️ Final decision cannot be made before the assigned evaluator(s) submit their feedback.
                                </div>
                            @elseif (!in_array($application->final_decision ?? '', ['approved', 'rejected']))
                                <div class="form-submit-row">
                                    <button type="submit" class="btn">Save Final Decision</button>
                                </div>
                            @else
                                <p class="feedback-text" style="color: #059669; font-weight: 600; margin-top: 15px; display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 16px;">✓</span> Decision Finalized ({{ ucfirst($application->final_decision) }})
                                </p>
                            @endif
                        </form>
                    </section>
                @endif

                @if ($application->application_type === 'APEL C')
                    <section class="card form-main-card" style="margin-top: 20px; border-top: 4px solid #8B1E3F;">
                        <h3 class="side-form-title">Final Credit Decision</h3>

                        @php
                            $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();
                        @endphp

                        @if ($submission && $submission->graded_at)
                            <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                                <strong style="display: block; font-size: 13px; color: #b45309; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Grading Outcome</strong>
                                <div style="display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #374151;">
                                    @if (($application->assessment_type ?? '') === 'portfolio')
                                        <div><strong>Result:</strong> 
                                            @if ($submission->result === 'pass')
                                                <span style="font-weight: 700; color: #10b981;">Approved (Recommended for Credit)</span>
                                            @else
                                                <span style="font-weight: 700; color: #ef4444;">Rejected (Not Recommended)</span>
                                            @endif
                                        </div>
                                    @else
                                        <div><strong>Score:</strong> {{ $submission->score }}%</div>
                                        <div><strong>Result:</strong> 
                                            @if ($submission->result === 'pass')
                                                <span style="font-weight: 700; color: #10b981;">Pass (Recommended for Credit)</span>
                                            @else
                                                <span style="font-weight: 700; color: #ef4444;">Fail (Not Recommended)</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($submission->grader_feedback)
                                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #f59e0b; color: #4b5563; font-style: italic;">
                                            "{{ $submission->grader_feedback }}"
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div style="background: #f3f4f6; border: 1px solid #e5e7eb; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #6b7280; text-align: center;">
                                No assessment grading has been completed yet.
                            </div>
                        @endif

                        @php
                            $bothReviewedC = !empty($submission) && !empty($submission->graded_at);
                        @endphp

                        <form method="POST"
                            action="{{ route('admin.applications.finalize_apel_c', $application->_id) }}">
                            @csrf

                            <label>Credit Decision</label>
                            @php
                                $defaultDecision = $application->credit_decision ?? 'pending';
                                if ($submission && $submission->result === 'fail' && $defaultDecision === 'pending') {
                                    $defaultDecision = 'rejected';
                                }
                            @endphp
                            <select name="credit_decision" required {{ !$bothReviewedC || in_array($application->credit_decision ?? '', ['approved', 'rejected']) ? 'disabled' : '' }}>
                                <option value="pending"
                                    {{ $defaultDecision === 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="approved"
                                    {{ $defaultDecision === 'approved' ? 'selected' : '' }} {{ $submission && $submission->result === 'fail' ? 'disabled' : '' }}>
                                    Approved
                                </option>
                                <option value="rejected"
                                    {{ $defaultDecision === 'rejected' ? 'selected' : '' }}>
                                    Rejected
                                </option>
                            </select>

                            <label>Approved Credit Hours</label>
                            @php
                                $hours = \App\Http\Controllers\Admin\ApplicationManagementController::getCreditHoursFromCourseCode($application->credit_course_code);
                            @endphp
                            <input type="number" name="credit_hours_approved" id="credit_hours_approved" readonly
                                value="{{ $application->credit_hours_approved ?? $hours }}">

                            <label>Course Code</label>
                            <input type="text" name="credit_course_code" readonly
                                value="{{ old('credit_course_code', $application->credit_course_code) }}">

                            <label>Course Name</label>
                            <input type="text" name="credit_course_name" readonly
                                value="{{ old('credit_course_name', $application->credit_course_name) }}">

                            <label>Credit Remarks</label>
                            <textarea name="credit_remarks" rows="6" placeholder="Write final credit decision remarks here..." {{ !$bothReviewedC || in_array($application->credit_decision ?? '', ['approved', 'rejected']) ? 'readonly' : '' }}>{{ old('credit_remarks', $application->credit_remarks) }}</textarea>

                            <div class="tip-box tip-box-light">
                                <strong>Important</strong>
                                <p>
                                    This is the final APEL C credit outcome shown to the student after assessment and
                                    grading are completed.
                                </p>
                            </div>

                            @if (!$bothReviewedC)
                                <div class="alert alert-warning" style="background-color: #fffbeb; border: 1px solid #fef3c7; color: #b45309; padding: 10px 14px; border-radius: 8px; margin-top: 15px; font-size: 13.5px; font-weight: 600; text-align: center;">
                                    ⚠️ Final decision cannot be made before grading is completed by both evaluators.
                                </div>
                            @elseif (!in_array($application->credit_decision ?? '', ['approved', 'rejected']))
                                <div class="form-submit-row">
                                    <button type="submit" class="btn">Save Credit Decision</button>
                                </div>
                            @else
                                <p class="feedback-text" style="color: #059669; font-weight: 600; margin-top: 15px; display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 16px;">✓</span> Decision Finalized ({{ ucfirst($application->credit_decision) }})
                                </p>
                            @endif
                        </form>
                    </section>
                @endif
                @endif
            </aside>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Credit hours approved is calculated dynamically from course code
            const scoreSelects = document.querySelectorAll('.clo-score');
            const recSelect = document.getElementById('recommendation_status');
            
            function checkScores() {
                if (!recSelect) return;
                let anyLow = false;
                scoreSelects.forEach(select => {
                    if (parseInt(select.value) < 3) {
                        anyLow = true;
                    }
                });
                if (anyLow) {
                    recSelect.value = "NOT recommended";
                    recSelect.style.borderColor = "#ef4444";
                } else {
                    recSelect.value = "Recommended";
                    recSelect.style.borderColor = "";
                }
            }
            
            scoreSelects.forEach(select => {
                select.addEventListener('change', checkScores);
            });
        });
    </script>
@endsection
