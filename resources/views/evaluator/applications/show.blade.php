@extends('layouts.app')

@section('content')
    <div class="container eval-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Evaluator Review</span>
                <h2>Review Application</h2>
                <p class="muted page-hero-text">
                    Review the submitted internal application details and update the application result.
                </p>
            </div>

            <div class="hero-actions" style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('student.applications.print', $application->_id) }}" target="_blank" class="btn btn-secondary">
                    🖨️ Export PDF Report
                </a>
                
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">
                    Back to Assigned Applications
                </a>

                @if ($application->application_type === 'APEL C' && ($application->assessment_type ?? '') !== 'portfolio')
                    @php
                        $status = $application->status ?? 'Pre-Application Submitted';
                        $isFinalized = in_array($application->credit_decision ?? '', ['approved', 'rejected']) || in_array($status, ['Final Approved', 'Final Rejected']);
                        $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();
                        $paperExists = \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)->exists();
                    @endphp
                    @if (!$isFinalized && !($isEvaluator2 && $paperExists))
                        <a href="{{ route('evaluator.assessment.papers.create', $application->_id) }}" class="btn">
                            Upload Assessment PDF
                        </a>
                    @endif
                @endif
            </div>
        </section>

        <div class="review-layout">
            <div class="review-main">
                <section class="record-card">
                    <div class="record-top">
                        <div>
                            <p class="record-kicker">{{ $application->application_type }}</p>
                            <h3>{{ $application->program_applied }}</h3>
                        </div>

                        <div class="record-top-right">
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
                        </div>
                    </div>

                    <div class="record-meta-grid">
                        <div class="meta-box">
                            <span class="meta-label">Submission Date</span>
                            <strong>{{ $application->submission_date }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Current Status</span>
                            <strong>{{ $application->status ?? 'Pre-Application Submitted' }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Application Type</span>
                            <strong>{{ $application->application_type }}</strong>
                        </div>
                    </div>

                    @if ($application->application_type === 'APEL A')
                        <div class="record-panel">
                            <h4>Internal Application Form Details</h4>
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
                        </div>
                    @else
                        <div class="record-panel">
                            <h4>Course & Submitted Portfolio</h4>

                            <p class="feedback-text" style="font-size: 14px; margin-bottom: 15px;">
                                <strong>Applied Course:</strong>
                                <span style="font-weight: 600; color: #1f2937;">
                                    {{ $application->credit_course_name ?? 'Not provided' }}
                                    @if ($application->credit_course_code)
                                         ({{ $application->credit_course_code }})
                                    @endif
                                </span>
                            </p>

                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e5e7eb;">
                                <p style="font-size: 13.5px; font-weight: 700; color: #8B1E3F; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Submitted Portfolio Files</p>
                                
                                @if (!empty($application->portfolio_file))
                                    <ul style="margin: 0; padding-left: 20px; font-size: 13.5px; list-style-type: disc;">
                                        @foreach ($application->portfolio_file as $file)
                                            @php
                                                $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                                $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                                            @endphp
                                            <li style="margin-bottom: 6px;">
                                                <a href="{{ asset('storage/' . $filePath) }}" target="_blank" style="color: #8B1E3F; text-decoration: underline; font-weight: 600;">
                                                    {{ $fileName }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p style="font-size: 13px; color: #6b7280; font-style: italic; margin: 0;">No portfolio files uploaded.</p>
                                @endif

                                @if (!empty($application->evidence_file))
                                    <p style="font-size: 13.5px; font-weight: 700; color: #8B1E3F; margin-top: 15px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Additional Evidence File(s)</p>
                                    <ul style="margin: 0; padding-left: 20px; font-size: 13.5px; list-style-type: disc;">
                                        @foreach ($application->evidence_file as $file)
                                            @php
                                                $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                                $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                                            @endphp
                                            <li style="margin-bottom: 6px;">
                                                <a href="{{ asset('storage/' . $filePath) }}" target="_blank" style="color: #8B1E3F; text-decoration: underline; font-weight: 600;">
                                                    {{ $fileName }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                        $student = \App\Models\User::find($application->user_id);
                                    @endphp
                                    <div class="record-meta-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 15px;">
                                        <div class="meta-box">
                                            <span class="meta-label">Full Name</span>
                                            <strong>{{ $personal['name'] ?? ($student->name ?? 'N/A') }}</strong>
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
                    </div>
                </section>
            </div>

            <aside class="review-side">
                @if ($application->application_type === 'APEL A')
                    <section class="card form-main-card">
                        @php
                            $isEvaluator1 = (string) ($application->evaluator_id ?? '') === (string) Auth::id();
                            $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();
                            
                            $hasBeenReviewedThisUser = false;
                            $userDecision = 'pending';
                            $userFeedback = '';
                            
                            if ($isEvaluator1 && !empty($application->evaluator_1_reviewed_at)) {
                                $hasBeenReviewedThisUser = true;
                                $userDecision = $application->evaluator_1_decision;
                                $userFeedback = $application->evaluator_1_feedback;
                            } elseif ($isEvaluator2 && !empty($application->evaluator_2_reviewed_at)) {
                                $hasBeenReviewedThisUser = true;
                                $userDecision = $application->evaluator_2_decision;
                                $userFeedback = $application->evaluator_2_feedback;
                            }
                        @endphp

                        @if ($hasBeenReviewedThisUser)
                            <h3 class="side-form-title">Review Summary</h3>
                            
                            <div class="review-details" style="display: flex; flex-direction: column; gap: 16px; margin-top: 15px;">
                                <div class="detail-group">
                                    <label style="font-weight: 600; font-size: 13px; color: #8B1E3F; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Admission Decision</label>
                                    <div style="font-size: 15px; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                                        @if ($userDecision === 'recommended')
                                            <span style="display: inline-block; width: 8px; height: 8px; background-color: #10b981; border-radius: 50%;"></span>
                                            Recommended
                                        @elseif ($userDecision === 'not_recommended')
                                            <span style="display: inline-block; width: 8px; height: 8px; background-color: #ef4444; border-radius: 50%;"></span>
                                            Not Recommended
                                        @else
                                            <span style="display: inline-block; width: 8px; height: 8px; background-color: #6b7280; border-radius: 50%;"></span>
                                            Pending
                                        @endif
                                    </div>
                                </div>

                                <div class="detail-group">
                                    <label style="font-weight: 600; font-size: 13px; color: #8B1E3F; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Evaluator Feedback</label>
                                    <div style="font-size: 14px; color: #4b5563; line-height: 1.5; background: #fdfbfb; padding: 12px 16px; border-radius: 12px; border: 1px solid #f3ebee; white-space: pre-wrap;">{{ $userFeedback ?? 'No feedback provided.' }}</div>
                                </div>
                                
                                @if (in_array($application->status ?? '', ['Final Approved', 'Final Rejected']))
                                    <div style="margin-top: 10px; padding: 12px; background-color: #f0fdf4; border: 1px solid #dcfce7; border-radius: 12px; text-align: center;">
                                        <span style="font-size: 12px; font-weight: 700; color: #15803d; text-transform: uppercase; display: block; margin-bottom: 4px;">Decision Finalized</span>
                                        <p style="margin: 0; font-size: 13px; color: #166534;">This application has been finalized as <strong>{{ $application->status }}</strong>.</p>
                                    </div>
                                @endif

                                <div style="margin-top: 10px; padding-top: 15px; border-top: 1px solid #f3ebee;">
                                    <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary" style="width: 100%; text-align: center; display: block;">Back to Assigned Applications</a>
                                </div>
                            </div>
                        @else
                            <h3 class="side-form-title">Update Application</h3>

                            @if ($errors->any())
                                <div class="alert alert-error">
                                    <ul style="padding-left: 18px;">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('evaluator.applications.update', $application->_id) }}">
                                @csrf

                                <label>Admission Decision</label>
                                <select name="admission_decision" required>
                                    <option value="pending"
                                        {{ $userDecision == 'pending' ? 'selected' : '' }}>
                                        Pending
                                    </option>
                                    <option value="recommended"
                                        {{ $userDecision == 'recommended' ? 'selected' : '' }}>
                                        Recommended
                                    </option>
                                    <option value="not_recommended"
                                        {{ $userDecision == 'not_recommended' ? 'selected' : '' }}>
                                        Not Recommended
                                    </option>
                                </select>

                                <label>Evaluator Feedback</label>
                                <textarea name="evaluator_feedback" rows="8" placeholder="Write your review comments here...">{{ $userFeedback }}</textarea>

                                <div class="form-submit-row">
                                    <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn">Update Application</button>
                                </div>
                            </form>
                        @endif
                    </section>
                @else
                    {{-- APEL C Workflow Progress Summary --}}
                    @php
                        $isPortfolioMode = ($application->assessment_type ?? '') === 'portfolio';
                        $paper = null;
                        $submission = null;
                        if ($isPortfolioMode) {
                            $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();
                        } else {
                            $paper = \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)->first();
                            $submission = $paper ? \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first() : null;
                        }
                    @endphp

                    @if ($isPortfolioMode)
                        <section class="card form-main-card" style="border-top: 4px solid #8B1E3F;">
                            <h3 class="side-form-title" style="margin-bottom: 20px;">Portfolio Evaluation</h3>

                            @if ($submission)
                                @php
                                    $isEvaluator1 = (string) ($application->evaluator_id ?? '') === (string) Auth::id();
                                    $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();
                                    
                                    $hasGradedThisUser = false;
                                    $existingScore = null;
                                    $existingFeedback = null;
                                    $existingResult = null;
                                    
                                    if ($isEvaluator1 && !empty($submission->evaluator_1_graded_at)) {
                                        $hasGradedThisUser = true;
                                        $existingScore = $submission->evaluator_1_score;
                                        $existingFeedback = $submission->evaluator_1_feedback;
                                        $existingResult = $submission->evaluator_1_result;
                                    } elseif ($isEvaluator2 && !empty($submission->evaluator_2_graded_at)) {
                                        $hasGradedThisUser = true;
                                        $existingScore = $submission->evaluator_2_score;
                                        $existingFeedback = $submission->evaluator_2_feedback;
                                        $existingResult = $submission->evaluator_2_result;
                                    }
                                @endphp

                                @if ($submission->graded_at)
                                    <div style="background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 15px;">
                                        <p style="margin: 0; font-size: 13.5px;">Consolidated Result: <strong style="color: {{ $submission->result === 'pass' ? '#10b981' : '#ef4444' }};">{{ $submission->result === 'pass' ? 'Approved' : 'Rejected' }}</strong></p>
                                    </div>
                                @endif

                                <div style="margin-top: 15px;">
                                    @if ($hasGradedThisUser)
                                        <div style="background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 12px; font-size: 13.5px; line-height: 1.5;">
                                            <div>Your Score: <strong>{{ $existingScore }}%</strong></div>
                                            <div>Result: <strong style="color: {{ $existingResult === 'pass' ? '#10b981' : '#ef4444' }};">{{ ucfirst($existingResult) }}</strong></div>
                                            <div style="margin-top: 5px; font-style: italic; color: #4b5563;">Feedback: "{{ $existingFeedback }}"</div>
                                        </div>
                                        <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}" class="btn btn-full btn-secondary" style="width: 100%; text-align: center; padding: 10px; display: block; background: #f3f4f6; color: #4b5563; font-weight: 600; text-decoration: none; border-radius: 6px; border: 1px solid #e5e7eb;">
                                            View Rubric Evaluation
                                        </a>
                                    @else
                                        <p style="font-size: 13px; color: #4b5563; margin-bottom: 15px; line-height: 1.4;">
                                            Review the candidate's uploaded portfolio and submit the scoring rubric.
                                        </p>
                                        <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}" class="btn btn-full" style="width: 100%; text-align: center; padding: 10px; display: block; background: #8B1E3F; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 6px;">
                                            Evaluate Portfolio Now
                                        </a>
                                    @endif
                                </div>
                            @else
                                <p style="font-size: 13px; color: #ef4444;">Evaluation submission is not initialized yet.</p>
                            @endif
                        </section>
                    @else
                        <section class="card form-main-card" style="border-top: 4px solid #8B1E3F;">
                            <h3 class="side-form-title" style="margin-bottom: 20px;">Assessment Progress</h3>

                            <!-- Step 1: Assessment Paper -->
                            <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 14px; color: #4b5563;">1. Assessment Paper</strong>
                                    @if ($paper)
                                        <span class="badge badge-approved" style="font-size: 11px; padding: 3px 8px; background-color: #d4edda; color: #155724;">Uploaded</span>
                                    @else
                                        <span class="badge badge-pending" style="font-size: 11px; padding: 3px 8px; background-color: #fff3cd; color: #856404;">Pending</span>
                                    @endif
                                </div>
                                @if ($paper)
                                    <p style="margin: 0; font-size: 13px; color: #1f2937; font-weight: 500;">{{ $paper->title }}</p>
                                    <a href="{{ asset('storage/' . $paper->question_file) }}" target="_blank" style="display: inline-block; margin-top: 5px; font-size: 12px; color: #8B1E3F; font-weight: 600; text-decoration: none;">View Paper PDF</a>
                                @else
                                    <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280;">No assessment paper has been uploaded yet.</p>
                                    <a href="{{ route('evaluator.assessment.papers.create', $application->_id) }}" class="btn btn-sm" style="width: 100%; text-align: center; display: block; font-size: 12px; padding: 8px;">Upload Paper</a>
                                @endif
                            </div>

                            <!-- Step 2: Student Submission -->
                            <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 14px; color: #4b5563;">2. Answer Submission</strong>
                                    @if ($submission)
                                        <span class="badge badge-approved" style="font-size: 11px; padding: 3px 8px; background-color: #d4edda; color: #155724;">Submitted</span>
                                    @else
                                        <span class="badge badge-pending" style="font-size: 11px; padding: 3px 8px; background-color: #fff3cd; color: #856404;">Pending</span>
                                    @endif
                                </div>
                                @if ($submission)
                                    <p style="margin: 0; font-size: 12px; color: #6b7280;">Submitted on: <strong style="color: #1f2937;">{{ $submission->submitted_at ?? 'N/A' }}</strong></p>
                                    <a href="{{ asset('storage/' . $submission->answer_file) }}" target="_blank" style="display: inline-block; margin-top: 5px; font-size: 12px; color: #8B1E3F; font-weight: 600; text-decoration: none;">Open Submitted Answer</a>
                                @else
                                    <p style="margin: 0; font-size: 12px; color: #6b7280;">Awaiting student's answer upload.</p>
                                 @endif
                            </div>

                            <!-- Step 3: Grading -->
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 14px; color: #4b5563;">3. Assessment Grade</strong>
                                    @if ($submission && $submission->graded_at)
                                        <span class="badge badge-approved" style="font-size: 11px; padding: 3px 8px; background-color: #d4edda; color: #155724;">Graded</span>
                                    @else
                                        <span class="badge badge-pending" style="font-size: 11px; padding: 3px 8px; background-color: #fff3cd; color: #856404;">Pending</span>
                                    @endif
                                </div>
                                @if ($submission)
                                    @if ($submission->graded_at)
                                        <div style="background: #f9fafb; padding: 10px; border-radius: 6px; border: 1px solid #e5e7eb;">
                                            <p style="margin: 0 0 4px 0; font-size: 13px;">Score: <strong style="color: #1f2937; font-size: 14px;">{{ $submission->score }}%</strong></p>
                                            <p style="margin: 0 0 4px 0; font-size: 13px;">Result: <strong style="color: {{ $submission->result === 'pass' ? '#10b981' : '#ef4444' }}; font-size: 14px;">{{ ucfirst($submission->result) }}</strong></p>
                                            @if ($submission->grader_feedback)
                                                <p style="margin: 8px 0 0 0; font-size: 12px; color: #4b5563; font-style: italic; border-top: 1px dashed #d1d5db; padding-top: 8px;">Feedback: "{{ $submission->grader_feedback }}"</p>
                                            @endif
                                        </div>
                                        <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}" style="display: block; text-align: center; margin-top: 10px; font-size: 12px; color: #8B1E3F; font-weight: 600; text-decoration: none;">View Full Grading Details</a>
                                    @else
                                        <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280;">Answer is ready for grading.</p>
                                        <a href="{{ route('evaluator.assessment.grading.show', $submission->_id) }}" class="btn btn-sm" style="width: 100%; text-align: center; display: block; font-size: 12px; padding: 8px;">Grade Now</a>
                                    @endif
                                @else
                                    <p style="margin: 0; font-size: 12px; color: #6b7280;">Cannot grade until student submits answer.</p>
                                @endif
                            </div>
                        </section>
                    @endif
                @endif
            </aside>
        </div>
    </div>
@endsection
