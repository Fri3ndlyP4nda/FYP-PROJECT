@extends('layouts.app')

@section('content')
    <style>
        .form-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            flex-wrap: wrap;
        }
        .tab-link {
            border: none;
            background: transparent;
            color: #6b7280;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 13.5px;
        }
        .tab-link.active {
            background: #8B1E3F;
            color: #ffffff;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .dynamic-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 24px !important;
            background: #ffffff !important;
            border: 1px solid #e2d7da !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }
        .dynamic-table:not(.language-table) {
            table-layout: fixed !important;
        }
        .dynamic-table th {
            background: #fdfafb !important;
            color: #8B1E3F !important;
            font-weight: 700 !important;
            padding: 10px 8px !important;
            border: 1px solid #e2d7da !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
            line-height: 1.3 !important;
            vertical-align: middle !important;
        }
        .dynamic-table td {
            padding: 10px 12px !important;
            border: 1px solid #e2d7da !important;
            vertical-align: middle !important;
        }
        .dynamic-table td input,
        .dynamic-table td select {
            width: 100% !important;
            padding: 8px 10px !important;
            margin-bottom: 0 !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            background-color: #ffffff !important;
            box-sizing: border-box !important;
        }
        .dynamic-table td textarea {
            width: 100% !important;
            height: 60px !important;
            min-height: 50px !important;
            resize: vertical !important;
            padding: 8px 10px !important;
            margin-bottom: 0 !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            background-color: #ffffff !important;
            box-sizing: border-box !important;
        }
        .skills-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 6px !important;
            max-height: 140px !important;
            overflow-y: auto !important;
            border: 1px solid #e2d7da !important;
            padding: 8px !important;
            background: #faf9fa !important;
            border-radius: 6px !important;
            box-sizing: border-box !important;
        }
        .skills-grid label {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 11.5px !important;
            font-weight: 500 !important;
            color: #4b5563 !important;
            margin-bottom: 0 !important;
            cursor: pointer !important;
        }
        .skills-grid label input[type="checkbox"] {
            width: 14px !important;
            height: 14px !important;
            margin: 0 !important;
            cursor: pointer !important;
        }
        .language-table th, .language-table td {
            text-align: center;
            vertical-align: middle;
        }
        .language-table td:first-child {
            text-align: left;
            font-weight: 600;
        }
        .language-table input[type="radio"] {
            width: auto;
            margin: 0 auto;
            display: block;
        }
        .month-year-picker {
            display: flex !important;
            flex-direction: column !important;
            gap: 4px !important;
            width: 100% !important;
        }
        .month-year-picker .selects-row {
            display: flex !important;
            flex-direction: column !important;
            gap: 4px !important;
            width: 100% !important;
        }
        .month-year-picker select {
            width: 100% !important;
            padding: 6px 8px !important;
            font-size: 13px !important;
            border-radius: 6px !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            margin-bottom: 0 !important;
            box-sizing: border-box !important;
        }

        .referee-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            background: #f9fafb;
        }
        .referee-card h4 {
            margin-top: 0;
            color: #8B1E3F;
            font-weight: 600;
            font-size: 14.5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .referee-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .table-action-btn-row {
            margin-bottom: 15px;
            text-align: right;
        }
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
            grid-template-columns: repeat(12, 1fr);
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
        .row-card-body input[type="text"],
        .row-card-body select,
        .row-card-body textarea {
            width: 100% !important;
            box-sizing: border-box !important;
            padding: 8px 12px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            font-size: 13px !important;
            margin-bottom: 0 !important;
        }
        
        .education-grid .field-col:nth-child(1) { grid-column: span 3; }
        .education-grid .field-col:nth-child(2) { grid-column: span 9; }
        .education-grid .field-col:nth-child(3) { grid-column: span 6; }
        .education-grid .field-col:nth-child(4) { grid-column: span 6; }

        .employment-grid .field-col.employer-name { grid-column: span 6; }
        .employment-grid .field-col.contact-address { grid-column: span 6; }
        .employment-grid .field-col.time-from { grid-column: span 6; }
        .employment-grid .field-col.time-to { grid-column: span 6; }
        .employment-grid .field-col.position-held { grid-column: span 6; }
        .employment-grid .field-col.job-roles { grid-column: span 12; }

        .training-grid .field-col.course-name { grid-column: span 6; }
        .training-grid .field-col.location { grid-column: span 6; }
        .training-grid .field-col.date-duration { grid-column: span 6; }
        .training-grid .field-col.activity-type { grid-column: span 6; }
        .training-grid .field-col.skills-learnt { grid-column: span 12; }

        .other-skills-grid .field-col.other-activities { grid-column: span 9; }
        .other-skills-grid .field-col.year { grid-column: span 3; }
        .other-skills-grid .field-col.skills-learnt { grid-column: span 12; }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            background: #f9fafb;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            max-height: 160px;
            overflow-y: auto;
            width: 100%;
        }
        .skills-grid label {
            display: flex !important;
            align-items: center;
            gap: 6px;
            font-size: 11.5px !important;
            font-weight: normal !important;
            color: #374151 !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            cursor: pointer;
        }
        .skills-grid input[type="checkbox"] {
            margin: 0 !important;
            width: 14px !important;
            height: 14px !important;
            cursor: pointer;
        }
    </style>

    <div class="container app-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Edit Application Draft</span>
                <h2>Continue APEL Application</h2>
                <p class="muted page-hero-text">
                    Complete your saved APEL draft and submit it for evaluation.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">View My Applications</a>
            </div>
        </section>

        <div class="form-split-layout">
            <div class="card form-main-card">
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul style="padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('student.applications.update', $application->_id) }}" enctype="multipart/form-data" id="apel-application-form">
                    @csrf
                    @method('PUT')

                    <div class="form-row">
                        <div>
                            <label>Application Type</label>
                            <select name="application_type" id="application-type-select" required>
                                <option value="">-- Select --</option>
                                <option value="APEL A" {{ old('application_type', $application->application_type) == 'APEL A' ? 'selected' : '' }}>APEL A</option>
                                <option value="APEL C" {{ old('application_type', $application->application_type) == 'APEL C' ? 'selected' : '' }}>APEL C</option>
                            </select>
                        </div>

                        <div id="apel-a-programme-box" style="display: none;">
                            <label>Programme Applied</label>
                            <select name="program_applied">
                                <option value="">-- Select Master Programme --</option>
                                @foreach ($programmes as $programme)
                                    <option value="{{ $programme->name }}"
                                        {{ old('program_applied', $application->program_applied) == $programme->name ? 'selected' : '' }}>
                                        {{ $programme->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="apel-c-course-box" style="display: none;">
                            <label>APEL C Course</label>
                            <select name="course_id">
                                <option value="">-- Select Course --</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->_id }}"
                                        {{ old('course_id', $application->credit_course_code) == $course->course_code ? 'selected' : '' }}>
                                        {{ $course->course_name }} ({{ $course->course_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- APEL A Internal Form --}}
                    <div id="apel-a-form-box" style="display: none;">
                        <hr>
                        <h3>APEL A Internal Application Form</h3>

                        <label>Identity Card (IC) No.</label>
                        <input type="text" name="ic_no" id="ic-no-input" value="{{ old('ic_no', $application->ic_no) }}" placeholder="Example: 951020-10-5033" style="margin-bottom: 4px !important;">
                        <div id="citizenship-indicator" style="font-size: 12px; font-weight: 600; margin-bottom: 15px; display: block;">
                            Please enter your 12-digit IC number.
                        </div>

                        <label>Age</label>
                        <input type="number" name="age" id="age-input" value="{{ old('age', $application->age) }}" min="18" max="100" placeholder="Example: 25" style="margin-bottom: 4px !important;">
                        <div id="age-warning" style="font-size: 12px; color: #ef4444; font-weight: 600; margin-bottom: 15px; display: none;">
                            ⚠️ Alert: APEL A for Master level access requires candidates to be at least 30 years of age.
                        </div>

                        <label>Name of University (Highest Qualification)</label>
                        <input type="text" name="university_name" value="{{ old('university_name', $application->university_name) }}" placeholder="Example: Universiti Teknologi Malaysia">

                        <label>Name of Company (Current Employment)</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $application->company_name) }}" placeholder="Example: Google Inc.">

                        <label>Highest Academic Qualification</label>
                        <input type="text" name="highest_qualification" id="qualification-input" value="{{ old('highest_qualification', $application->highest_qualification) }}"
                            placeholder="Example: Diploma in Computer Science" style="margin-bottom: 4px !important;">
                        <div id="qualification-warning" style="font-size: 12px; color: #ef4444; font-weight: 600; margin-bottom: 15px; display: none;">
                            ⚠️ Alert: The highest qualification for APEL A must start exactly with "Diploma" (e.g., Diploma in Computer Science).
                        </div>

                        <label>Current Job / Position</label>
                        <input type="text" name="current_job" value="{{ old('current_job', $application->current_job) }}"
                            placeholder="Example: IT Executive / Software Developer">

                        <label>Years of Working Experience</label>
                        <input type="number" name="working_experience_years" value="{{ old('working_experience_years', $application->working_experience_years) }}"
                            min="0" placeholder="Example: 5">

                        <label>Relevant Working Experience</label>
                        <textarea name="working_experience_details" rows="4"
                            placeholder="Briefly describe your working experience related to the selected programme.">{{ old('working_experience_details', $application->working_experience_details) }}</textarea>

                        <label>Reason for Applying APEL A</label>
                        <textarea name="reason_applying" rows="4" placeholder="Explain why you are applying through APEL A.">{{ old('reason_applying', $application->reason_applying) }}</textarea>
                    </div>

                    {{-- APEL C Form (Wizard Tabs) --}}
                    <div id="apel-c-form-box" style="display: none;">
                        <hr>
                        <h3 style="margin-bottom: 15px;">APEL C Pre-Application & Self-Assessment</h3>

                        <div class="form-tabs">
                            <button type="button" class="tab-link active" onclick="openTab(event, 'tab-particulars')">1. Particulars & Education</button>
                            <button type="button" class="tab-link" onclick="openTab(event, 'tab-experience')">2. Experience & Training</button>
                            <button type="button" class="tab-link" onclick="openTab(event, 'tab-skills')">3. Skills & Languages</button>
                            <button type="button" class="tab-link" onclick="openTab(event, 'tab-referees')">4. Referees & Self-Assessment</button>
                            <button type="button" class="tab-link" onclick="openTab(event, 'tab-declaration')">5. Uploads & Declaration</button>
                        </div>

                        {{-- TAB 1: Particulars & Education --}}
                        <div id="tab-particulars" class="tab-content active">
                            <!-- Target Semester has been disabled for now
                            <label>Target Semester</label>
                            <select name="target_semester">
                                <option value="">-- Select Semester --</option>
                                <option value="Semester 1" {{ old('target_semester', $application->target_semester) == 'Semester 1' ? 'selected' : '' }}>Semester 1</option>
                                <option value="Semester 2" {{ old('target_semester', $application->target_semester) == 'Semester 2' ? 'selected' : '' }}>Semester 2</option>
                                <option value="Semester 3" {{ old('target_semester', $application->target_semester) == 'Semester 3' ? 'selected' : '' }}>Semester 3</option>
                            </select>
                            -->

                            <h4 style="color: #8B1E3F; margin-top: 15px; margin-bottom: 10px;">PART A: PERSONAL PARTICULARS</h4>
                            <label>Full Name (As per IC)</label>
                            <input type="text" name="pre_app_data[personal_particulars][name]" value="{{ old('pre_app_data.personal_particulars.name', $application->pre_app_data['personal_particulars']['name'] ?? auth()->user()->name) }}" required>

                            <label>Matric No.</label>
                            <input type="text" name="pre_app_data[personal_particulars][matric_no]" value="{{ old('pre_app_data.personal_particulars.matric_no', $application->pre_app_data['personal_particulars']['matric_no'] ?? '') }}" placeholder="e.g. MEC244062">

                            <label>Identity Card No.</label>
                            <input type="text" name="pre_app_data[personal_particulars][ic_no]" value="{{ old('pre_app_data.personal_particulars.ic_no', $application->pre_app_data['personal_particulars']['ic_no'] ?? '') }}" placeholder="e.g. 851020105033" required>

                            <label>Highest Academic Qualification</label>
                            @php
                                $hq = $application->pre_app_data['personal_particulars']['highest_qualification'] ?? '';
                            @endphp
                            <select name="pre_app_data[personal_particulars][highest_qualification]" required>
                                <option value="">-- Select Qualification --</option>
                                <option value="PhD" {{ $hq === 'PhD' ? 'selected' : '' }}>PhD / Doctoral Degree</option>
                                <option value="Master" {{ $hq === 'Master' ? 'selected' : '' }}>Master's Degree</option>
                                <option value="Bachelor" {{ $hq === 'Bachelor' ? 'selected' : '' }}>Bachelor's Degree</option>
                                <option value="Diploma" {{ $hq === 'Diploma' ? 'selected' : '' }}>Diploma (Minimum Eligibility)</option>
                                <option value="Certificate" {{ $hq === 'Certificate' ? 'selected' : '' }}>Certificate</option>
                                <option value="Other" {{ $hq === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>

                            <h4 style="color: #8B1E3F; margin-top: 20px; margin-bottom: 10px;">PART B (i): FORMAL LEARNING (CERTIFICATED EDUCATION)</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addEducationRow()">+ Add Education</button>
                            </div>
                            <div class="cards-container" id="education-tbody">
                                @php
                                    $formal = $application->pre_app_data['formal_learning'] ?? [[]];
                                @endphp
                                @foreach ($formal as $idx => $item)
                                    <div class="row-card education-card" id="education-card-{{ $idx }}">
                                        <div class="row-card-header">
                                            <span>Education Entry #{{ $idx + 1 }}</span>
                                            @if($idx > 0)
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                                            @endif
                                        </div>
                                        <div class="row-card-body education-grid">
                                            <div class="field-col">
                                                <label>Year Awarded</label>
                                                <input type="text" name="pre_app_data[formal_learning][{{ $idx }}][year_awarded]" value="{{ $item['year_awarded'] ?? '' }}" placeholder="e.g. 2024">
                                            </div>
                                            <div class="field-col">
                                                <label>Title of Certification</label>
                                                <input type="text" name="pre_app_data[formal_learning][{{ $idx }}][title_of_certification]" value="{{ $item['title_of_certification'] ?? '' }}" placeholder="e.g. Cert">
                                            </div>
                                            <div class="field-col">
                                                <label>Level of Award</label>
                                                <input type="text" name="pre_app_data[formal_learning][{{ $idx }}][level_of_award]" value="{{ $item['level_of_award'] ?? '' }}" placeholder="e.g. Certificate">
                                            </div>
                                            <div class="field-col">
                                                <label>Awarding Body</label>
                                                <input type="text" name="pre_app_data[formal_learning][{{ $idx }}][awarding_body]" value="{{ $item['awarding_body'] ?? '' }}" placeholder="Awarding Body">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- TAB 2: Experience & Training --}}
                        <div id="tab-experience" class="tab-content">
                            <h4 style="color: #8B1E3F; margin-bottom: 10px;">PART B (ii): EXPERIENTIAL LEARNING (EMPLOYMENT HISTORY)</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addEmploymentRow()">+ Add Employer</button>
                            </div>
                            <div class="cards-container" id="employment-tbody">
                                @php
                                    $jobs = $application->pre_app_data['experiential_learning'] ?? [[]];
                                @endphp
                                @foreach ($jobs as $idx => $item)
                                    <div class="row-card employment-card" id="employment-card-{{ $idx }}">
                                        <div class="row-card-header">
                                            <span>Employer Entry #{{ $idx + 1 }}</span>
                                            @if($idx > 0)
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                                            @endif
                                        </div>
                                        <div class="row-card-body employment-grid">
                                            <div class="field-col employer-name">
                                                <label>Employer Name</label>
                                                <input type="text" name="pre_app_data[experiential_learning][{{ $idx }}][employer_name]" value="{{ $item['employer_name'] ?? '' }}" placeholder="e.g. Roche">
                                            </div>
                                            <div class="field-col contact-address">
                                                <label>Contact Address</label>
                                                <input type="text" name="pre_app_data[experiential_learning][{{ $idx }}][contact_address]" value="{{ $item['contact_address'] ?? '' }}" placeholder="Address">
                                            </div>
                                            <div class="field-col time-from">
                                                <label>From (Month/Year)</label>
                                                <div class="month-year-picker">
                                                    <div class="selects-row" style="display: flex !important; flex-direction: row !important; gap: 8px !important;">
                                                        <select class="select-month" id="time-from-month-{{ $idx }}" onchange="updateCombinedDate({{ $idx }}, 'from')">
                                                            <option value="">Month</option>
                                                            @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $m)
                                                                <option value="{{ $m }}">{{ $m }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select class="select-year" id="time-from-year-{{ $idx }}" onchange="updateCombinedDate({{ $idx }}, 'from')">
                                                            <option value="">Year</option>
                                                            @for($y = date('Y') + 2; $y >= 1970; $y--)
                                                                <option value="{{ $y }}">{{ $y }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <input type="hidden" name="pre_app_data[experiential_learning][{{ $idx }}][time_from]" value="{{ $item['time_from'] ?? '' }}" id="time-from-hidden-{{ $idx }}">
                                                </div>
                                            </div>
                                            <div class="field-col time-to">
                                                <label>To (Month/Year)</label>
                                                <div class="month-year-picker">
                                                    <div class="selects-row" id="to-selects-container-{{ $idx }}" style="display: flex !important; flex-direction: row !important; gap: 8px !important;">
                                                        <select class="select-month" id="time-to-month-{{ $idx }}" onchange="updateCombinedDate({{ $idx }}, 'to')">
                                                            <option value="">Month</option>
                                                            @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $m)
                                                                <option value="{{ $m }}">{{ $m }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select class="select-year" id="time-to-year-{{ $idx }}" onchange="updateCombinedDate({{ $idx }}, 'to')">
                                                            <option value="">Year</option>
                                                            @for($y = date('Y') + 2; $y >= 1970; $y--)
                                                                <option value="{{ $y }}">{{ $y }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div id="to-current-display-{{ $idx }}" style="display: none; font-weight: 600; color: #8B1E3F; font-size: 13px; padding: 6px 8px; border: 1px solid #d1d5db; background: #faf9fa; border-radius: 6px; text-align: center;">Current</div>
                                                    <input type="hidden" name="pre_app_data[experiential_learning][{{ $idx }}][time_to]" value="{{ $item['time_to'] ?? '' }}" id="time-to-hidden-{{ $idx }}">
                                                    <label style="font-size: 11px; font-weight: normal; margin-top: 4px; display: flex; align-items: center; gap: 4px; color: #4b5563; cursor: pointer; width: auto !important; margin-bottom: 0 !important; text-transform: none; letter-spacing: normal;">
                                                        <input type="checkbox" 
                                                               id="time-to-current-{{ $idx }}" 
                                                               onclick="toggleCurrentWorkCheckbox(this, {{ $idx }})" 
                                                               {{ (strtolower($item['time_to'] ?? '') === 'current' || strtolower($item['time_to'] ?? '') === 'present') ? 'checked' : '' }} 
                                                               style="width: 13px !important; height: 13px !important; margin: 0 !important; cursor: pointer;">
                                                        <span>Present</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="field-col position-held">
                                                <label>Position Held</label>
                                                <input type="text" name="pre_app_data[experiential_learning][{{ $idx }}][position_held]" value="{{ $item['position_held'] ?? '' }}" placeholder="Position">
                                            </div>
                                            <div class="field-col job-roles">
                                                <label>Job Roles / Performed</label>
                                                <textarea name="pre_app_data[experiential_learning][{{ $idx }}][job_roles]" placeholder="Roles / Duties" rows="3">{{ $item['job_roles'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <h4 style="color: #8B1E3F; margin-top: 20px; margin-bottom: 10px;">TRAINING ACTIVITIES</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addTrainingRow()">+ Add Training Activity</button>
                            </div>
                            <div class="cards-container" id="training-tbody">
                                @php
                                    $trainings = $application->pre_app_data['training_activities'] ?? [[]];
                                @endphp
                                @foreach ($trainings as $idx => $item)
                                    <div class="row-card training-card" id="training-card-{{ $idx }}">
                                        <div class="row-card-header">
                                            <span>Training Entry #{{ $idx + 1 }}</span>
                                            @if($idx > 0)
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                                            @endif
                                        </div>
                                        <div class="row-card-body training-grid">
                                            <div class="field-col course-name">
                                                <label>Course/Training Name</label>
                                                <input type="text" name="pre_app_data[training_activities][{{ $idx }}][course_name]" value="{{ $item['course_name'] ?? '' }}" placeholder="Course Title">
                                            </div>
                                            <div class="field-col location">
                                                <label>Location</label>
                                                <input type="text" name="pre_app_data[training_activities][{{ $idx }}][location]" value="{{ $item['location'] ?? '' }}" placeholder="Location">
                                            </div>
                                            <div class="field-col date-duration">
                                                <label>Date & Duration</label>
                                                <input type="text" name="pre_app_data[training_activities][{{ $idx }}][date_duration]" value="{{ $item['date_duration'] ?? '' }}" placeholder="e.g. Nov 2024">
                                            </div>
                                            <div class="field-col activity-type">
                                                <label>Activity Type</label>
                                                <select name="pre_app_data[training_activities][{{ $idx }}][activity_type]">
                                                    <option value="Technical" {{ ($item['activity_type'] ?? '') === 'Technical' ? 'selected' : '' }}>Technical</option>
                                                    <option value="Managerial" {{ ($item['activity_type'] ?? '') === 'Managerial' ? 'selected' : '' }}>Managerial</option>
                                                    <option value="Both" {{ ($item['activity_type'] ?? '') === 'Both' ? 'selected' : '' }}>Both</option>
                                                </select>
                                            </div>
                                            <div class="field-col skills-learnt">
                                                <label>What Have I Learnt? (Skills Checklist)</label>
                                                <div class="skills-grid" id="skills-grid-t{{ $idx }}">
                                                    @foreach (["Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills", "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility", "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"] as $sIdx => $sName)
                                                        <label>
                                                            <input type="checkbox" name="pre_app_data[training_activities][{{ $idx }}][skills_learnt][]" value="{{ $sIdx + 1 }}" {{ in_array($sIdx + 1, $item['skills_learnt'] ?? []) ? 'checked' : '' }}>
                                                            {{ $sIdx + 1 }}. {{ $sName }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- TAB 3: Skills & Languages --}}
                        <div id="tab-skills" class="tab-content">
                            <h4 style="color: #8B1E3F; margin-bottom: 10px;">PART B (iii): OTHER LEARNING SKILLS / ACTIVITIES</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addOtherSkillRow()">+ Add Activity</button>
                            </div>
                            <div class="cards-container" id="other-skills-tbody">
                                @php
                                    $otherSkills = $application->pre_app_data['other_learning_skills'] ?? [[]];
                                @endphp
                                @foreach ($otherSkills as $idx => $item)
                                    <div class="row-card other-skills-card" id="other-skills-card-{{ $idx }}">
                                        <div class="row-card-header">
                                            <span>Other Activity Entry #{{ $idx + 1 }}</span>
                                            @if($idx > 0)
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                                            @endif
                                        </div>
                                        <div class="row-card-body other-skills-grid">
                                            <div class="field-col other-activities">
                                                <label>Other Activity Description</label>
                                                <input type="text" name="pre_app_data[other_learning_skills][{{ $idx }}][other_activities]" value="{{ $item['other_activities'] ?? '' }}" placeholder="Description">
                                            </div>
                                            <div class="field-col year">
                                                <label>Year</label>
                                                <input type="text" name="pre_app_data[other_learning_skills][{{ $idx }}][year]" value="{{ $item['year'] ?? '' }}" placeholder="e.g. 2025">
                                            </div>
                                            <div class="field-col skills-learnt">
                                                <label>What Have I Learnt? (Skills Checklist)</label>
                                                <div class="skills-grid" id="skills-grid-o{{ $idx }}">
                                                    @foreach (["Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills", "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility", "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"] as $sIdx => $sName)
                                                        <label>
                                                            <input type="checkbox" name="pre_app_data[other_learning_skills][{{ $idx }}][skills_learnt][]" value="{{ $sIdx + 1 }}" {{ in_array($sIdx + 1, $item['skills_learnt'] ?? []) ? 'checked' : '' }}>
                                                            {{ $sIdx + 1 }}. {{ $sName }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <h4 style="color: #8B1E3F; margin-top: 20px; margin-bottom: 10px;">PART B (iv): LANGUAGE SKILLS</h4>
                            <table class="dynamic-table language-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2">LANGUAGE</th>
                                        <th colspan="4">Listening</th>
                                        <th colspan="4">Reading</th>
                                        <th colspan="4">Speaking</th>
                                        <th colspan="4">Writing</th>
                                    </tr>
                                    <tr>
                                        <th>1</th><th>2</th><th>3</th><th>4</th>
                                        <th>1</th><th>2</th><th>3</th><th>4</th>
                                        <th>1</th><th>2</th><th>3</th><th>4</th>
                                        <th>1</th><th>2</th><th>3</th><th>4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $langSkills = $application->pre_app_data['language_skills'] ?? [];
                                    @endphp
                                    @foreach (['Bahasa Malaysia' => 'bm', 'English Language' => 'en', 'Mandarin/Chinese' => 'zh'] as $name => $key)
                                        @php
                                            $savedVal = collect($langSkills)->firstWhere('language', $name) ?? [];
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $name }}
                                                <input type="hidden" name="pre_app_data[language_skills][{{ $loop->index }}][language]" value="{{ $name }}">
                                            </td>
                                            @foreach (['listening', 'reading', 'speaking', 'writing'] as $skill)
                                                @for ($i = 1; $i <= 4; $i++)
                                                    <td>
                                                        <input type="radio" name="pre_app_data[language_skills][{{ $parentLoop = $loop->parent->index }}][{{ $skill }}]" value="{{ $i }}" {{ ($savedVal[$skill] ?? 3) == $i ? 'checked' : '' }} required>
                                                    </td>
                                                @endfor
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div style="font-size: 11px; color: #6b7280; margin-top: -10px; margin-bottom: 20px;">
                                Scale Competency - 1: Poor; 2: Average; 3: Good; 4: Excellent
                            </div>
                        </div>

                        {{-- TAB 4: Referees & Self-Assessment --}}
                        <div id="tab-referees" class="tab-content">
                            <h4 style="color: #8B1E3F; margin-bottom: 10px;">PART C (ii): REFEREES (Relevant to Work Situation)</h4>
                            @php
                                $ref1 = $application->pre_app_data['referees'][0] ?? [];
                                $ref2 = $application->pre_app_data['referees'][1] ?? [];
                            @endphp
                            <div class="referee-card">
                                <h4>Referee 1</h4>
                                <div class="referee-grid">
                                    <div>
                                        <label>Name</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_name]" value="{{ $ref1['referee_name'] ?? '' }}" placeholder="Full Name" required>
                                    </div>
                                    <div>
                                        <label>Position</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_position]" value="{{ $ref1['referee_position'] ?? '' }}" placeholder="Position/Designation" required>
                                    </div>
                                    <div>
                                        <label>Organisation</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_organisation]" value="{{ $ref1['referee_organisation'] ?? '' }}" placeholder="Organisation" required>
                                    </div>
                                    <div>
                                        <label>Office Phone</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_phone_office]" value="{{ $ref1['referee_phone_office'] ?? '' }}" placeholder="Office No.">
                                    </div>
                                    <div>
                                        <label>Mobile Phone</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_phone_mobile]" value="{{ $ref1['referee_phone_mobile'] ?? '' }}" placeholder="Mobile No." required>
                                    </div>
                                    <div>
                                        <label>Email Address</label>
                                        <input type="email" name="pre_app_data[referees][0][referee_email]" value="{{ $ref1['referee_email'] ?? '' }}" placeholder="email@address.com" required>
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <label>Relationship</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_relationship]" value="{{ $ref1['referee_relationship'] ?? '' }}" placeholder="e.g. Ex-Supervisor" required>
                                    </div>
                                </div>
                            </div>

                            <div class="referee-card">
                                <h4>Referee 2</h4>
                                <div class="referee-grid">
                                    <div>
                                        <label>Name</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_name]" value="{{ $ref2['referee_name'] ?? '' }}" placeholder="Full Name" required>
                                    </div>
                                    <div>
                                        <label>Position</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_position]" value="{{ $ref2['referee_position'] ?? '' }}" placeholder="Position/Designation" required>
                                    </div>
                                    <div>
                                        <label>Organisation</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_organisation]" value="{{ $ref2['referee_organisation'] ?? '' }}" placeholder="Organisation" required>
                                    </div>
                                    <div>
                                        <label>Office Phone</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_phone_office]" value="{{ $ref2['referee_phone_office'] ?? '' }}" placeholder="Office No.">
                                    </div>
                                    <div>
                                        <label>Mobile Phone</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_phone_mobile]" value="{{ $ref2['referee_phone_mobile'] ?? '' }}" placeholder="Mobile No." required>
                                    </div>
                                    <div>
                                        <label>Email Address</label>
                                        <input type="email" name="pre_app_data[referees][1][referee_email]" value="{{ $ref2['referee_email'] ?? '' }}" placeholder="email@address.com" required>
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <label>Relationship</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_relationship]" value="{{ $ref2['referee_relationship'] ?? '' }}" placeholder="e.g. Manager" required>
                                    </div>
                                </div>
                            </div>

                            <h4 style="color: #8B1E3F; margin-top: 25px; margin-bottom: 10px;">APEL (C) SELF-ASSESSMENT FOR LEARNERS</h4>
                            <p style="font-size:12.5px; color:#5b626a; line-height:1.4; margin-bottom:15px;">
                                For each Course Learning Outcome (CLO), describe how you have learned this outcome through your former studies or working career.
                            </p>

                            <label><strong>CLO1:</strong> Analyse IT information security governance, risk management frameworks, policies, and standards.</label>
                            <textarea name="self_assessment[clo_descriptions][clo1]" rows="3" placeholder="Describe your experience/certifications related to CLO1..." required>{{ $application->self_assessment['clo_descriptions']['clo1'] ?? '' }}</textarea>

                            <label><strong>CLO2:</strong> Evaluate applications of security and management, providing justifications based on fundamental concepts.</label>
                            <textarea name="self_assessment[clo_descriptions][clo2]" rows="3" placeholder="Describe your experience/certifications related to CLO2..." required>{{ $application->self_assessment['clo_descriptions']['clo2'] ?? '' }}</textarea>

                            <label><strong>CLO3:</strong> Complete the cycle of risk identification, analysis, assessment, and control of security management systems.</label>
                            <textarea name="self_assessment[clo_descriptions][clo3]" rows="3" placeholder="Describe your experience/certifications related to CLO3..." required>{{ $application->self_assessment['clo_descriptions']['clo3'] ?? '' }}</textarea>

                            <label><strong>CLO4:</strong> Construct detailed organisation-wide security plans/policies, and measure safeguards using appropriate tools.</label>
                            <textarea name="self_assessment[clo_descriptions][clo4]" rows="3" placeholder="Describe your experience/certifications related to CLO4..." required>{{ $application->self_assessment['clo_descriptions']['clo4'] ?? '' }}</textarea>
                        </div>

                        {{-- TAB 5: Uploads & Declaration --}}
                        <div id="tab-declaration" class="tab-content">
                            <h4 style="color: #8B1E3F; margin-bottom: 10px;">PART C: PORTFOLIO & DECLARATION</h4>
                            
                            <div style="background: #fdfafb; border: 1px solid #e2d7da; padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                                <h5 style="color: #8B1E3F; margin-top: 0; margin-bottom: 8px; font-weight: 700; font-size: 13.5px;">📌 PORTFOLIO SUBMISSION INSTRUCTIONS</h5>
                                <p style="font-size: 12.5px; color: #4b5563; line-height: 1.5; margin-bottom: 0;">
                                    Please upload your completed <strong>APEL (C) Portfolio Submission Form PDF</strong>. 
                                    This single compiled document must include:
                                    <br>• The **Self-Assessment Essay** (minimum 500 words) addressing all Course Learning Outcomes (CLOs).
                                    <br>• Your **detailed CV / Resume**.
                                    <br>• All **supporting documents & evidence** (certificates, award letters, transcripts, etc.) combined at the end of the document.
                                </p>
                            </div>

                            <label><strong>Upload Complete Portfolio PDF</strong> <span style="color: #ef4444;">*</span></label>
                            <input type="file" name="portfolio_file[]" id="portfolio-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <div id="portfolio-preview-list" style="margin-top: 8px; font-size: 13px; color: #4b5563;">
                                @if(!empty($application->portfolio_file))
                                    <ul style="list-style: none; padding: 0;">
                                        @foreach($application->portfolio_file as $file)
                                            <li style="padding: 3px 0;">📄 <strong style="color: #1f2937;">{{ $file['name'] ?? basename($file['path']) }}</strong> (Already uploaded)</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <small style="display:block; margin-top:5px; color:#666; margin-bottom:15px;">
                                Allowed format: PDF, JPG, JPEG, PNG, DOC, DOCX. Maximum size: 5MB per file.
                            </small>

                            <h4 style="color: #8B1E3F; margin-top: 25px; margin-bottom: 10px;">PART D: SELF-DECLARATION</h4>
                            <div style="background: #fdfafb; border: 1px solid #faebef; padding: 14px; border-radius: 12px; margin-bottom: 15px;">
                                <label style="font-weight: 400; display: flex; align-items: flex-start; gap: 8px; font-size: 13px; line-height: 1.5; cursor: pointer; color: #374151;">
                                    <input type="checkbox" name="pre_app_data[self_declaration][confirmed]" value="1" {{ ($application->pre_app_data['self_declaration']['confirmed'] ?? false) ? 'checked' : '' }} required style="width: auto; margin-top: 4px;">
                                    <span>
                                        I hereby declare that all of the information/documents provided to support this application are authentic, true and accurate. I fully understand that the UTM reserves the right to reject my application if proven otherwise.
                                    </span>
                                </label>
                            </div>

                            <label>Name (As per IC)</label>
                            <input type="text" name="pre_app_data[self_declaration][name_as_per_ic]" value="{{ $application->pre_app_data['self_declaration']['name_as_per_ic'] ?? '' }}" placeholder="Full Name as per IC" required>

                            <label>Date Declared</label>
                            <input type="date" name="pre_app_data[self_declaration][date_declared]" value="{{ $application->pre_app_data['self_declaration']['date_declared'] ?? date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="form-submit-row" style="display: flex; justify-content: flex-end; align-items: center; gap: 8px;">
                        <span id="autosave-notice" style="font-size: 12px; color: #10b981; opacity: 0; transition: opacity 0.3s; margin-right: auto; font-weight: 500;">✓ Draft saved automatically</span>
                        <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="submit_type" value="draft" class="btn btn-secondary" formnovalidate>Save Draft</button>
                        <button type="submit" name="submit_type" value="submit" class="btn" id="submit-application-btn">Submit Application</button>
                    </div>
                </form>
            </div>

            <aside class="info-side-card">
                <!-- APEL.A T-7 Requirements -->
                <div id="apel-a-requirements-card" style="background: #fdfafb; border: 1px solid rgba(139, 30, 63, 0.15); border-radius: 12px; padding: 16px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03); display: none;">
                    <span style="font-size: 10px; font-weight: 700; color: #8B1E3F; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">APEL.A T-7 Requirements</span>
                    <h4 style="margin-top: 0; margin-bottom: 8px; color: #30030f; font-size: 14px; font-weight: 700;">Basic Access Eligibility</h4>
                    <ul class="check-list" style="margin-bottom: 0; font-size: 12.5px; color: #4b5563; line-height: 1.5; padding-left: 15px;">
                        <li><strong>Malaysian Citizens</strong></li>
                        <li><strong>At least 30 years of age</strong> at the time of application.</li>
                        <li><strong>Hold exactly a Diploma</strong> (STPM/SPM are not eligible, and degree holders do not qualify for APEL A).</li>
                        <li><strong>Possess related work experience</strong>.</li>
                    </ul>
                </div>

                <!-- APEL.C Requirements -->
                <div id="apel-c-requirements-card" style="background: #fcfdfd; border: 1px solid rgba(13, 148, 136, 0.15); border-radius: 12px; padding: 16px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03); display: none;">
                    <span style="font-size: 10px; font-weight: 700; color: #0d9488; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">APEL.C Requirements</span>
                    <h4 style="margin-top: 0; margin-bottom: 8px; color: #0f3f3b; font-size: 14px; font-weight: 700;">Credit Award Eligibility</h4>
                    <ul class="check-list" style="margin-bottom: 0; font-size: 12.5px; color: #4b5563; line-height: 1.5; padding-left: 15px;">
                        <li><strong>Hold at least a Diploma</strong> qualification.</li>
                        <li><strong>At least 3 years work experience</strong> in a related field.</li>
                        <li><strong>Professional certificates</strong> must be valid within 5 years.</li>
                    </ul>
                </div>

                <span class="side-label">Draft Guide</span>
                <h3>Draft Application</h3>

                <ul class="check-list">
                    <li>This is a saved application draft.</li>
                    <li>You can update details and hit "Save Draft" again to store progress.</li>
                    <li>Verify all dynamic tables and details are complete before clicking "Submit Application".</li>
                    <li>Submitting triggers coordinate validations.</li>
                </ul>

                <div class="tip-box">
                    <strong>Tip</strong>
                    <p>
                        Ensure your 500-word Self-Assessment report describes how you achieved the 4 Course Learning Outcomes.
                    </p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function updateCombinedDate(idx, type) {
            const hiddenInput = document.getElementById(`time-${type}-hidden-${idx}`);
            const parent = hiddenInput.parentElement;
            const monthSel = parent.querySelector('.select-month');
            const yearSel = parent.querySelector('.select-year');
            
            if (monthSel && yearSel && hiddenInput) {
                if (monthSel.value && yearSel.value) {
                    hiddenInput.value = `${monthSel.value} ${yearSel.value}`;
                } else {
                    hiddenInput.value = '';
                }
            }
            if (typeof saveFormData === 'function') {
                saveFormData();
            }
        }

        function toggleCurrentWorkCheckbox(checkbox, idx) {
            const selectsContainer = document.getElementById(`to-selects-container-${idx}`);
            const currentDisplay = document.getElementById(`to-current-display-${idx}`);
            const hiddenInput = document.getElementById(`time-to-hidden-${idx}`);
            
            if (checkbox.checked) {
                if (selectsContainer) selectsContainer.style.display = 'none';
                if (currentDisplay) currentDisplay.style.display = 'block';
                if (hiddenInput) hiddenInput.value = 'Current';
            } else {
                if (selectsContainer) selectsContainer.style.display = 'flex';
                if (currentDisplay) currentDisplay.style.display = 'none';
                
                const monthSel = document.getElementById(`time-to-month-${idx}`);
                const yearSel = document.getElementById(`time-to-year-${idx}`);
                if (monthSel && yearSel && hiddenInput) {
                    if (monthSel.value && yearSel.value) {
                        hiddenInput.value = `${monthSel.value} ${yearSel.value}`;
                    } else {
                        hiddenInput.value = '';
                    }
                }
            }
            if (typeof saveFormData === 'function') {
                saveFormData();
            }
        }

        function initMonthYearPickers() {
            const rows = document.querySelectorAll('#employment-tbody .row-card');
            rows.forEach((row, idx) => {
                const fromHidden = document.getElementById(`time-from-hidden-${idx}`);
                if (fromHidden) {
                    const val = fromHidden.value.trim();
                    if (val) {
                        const parts = val.split(' ');
                        if (parts.length === 2) {
                            const monthSel = document.getElementById(`time-from-month-${idx}`);
                            const yearSel = document.getElementById(`time-from-year-${idx}`);
                            if (monthSel) monthSel.value = parts[0];
                            if (yearSel) yearSel.value = parts[1];
                        }
                    }
                }

                const toHidden = document.getElementById(`time-to-hidden-${idx}`);
                const currentCheckbox = document.getElementById(`time-to-current-${idx}`);
                if (toHidden) {
                    const val = toHidden.value.trim();
                    if (val.toLowerCase() === 'current' || val.toLowerCase() === 'present') {
                        if (currentCheckbox) currentCheckbox.checked = true;
                        toggleCurrentWorkCheckbox(currentCheckbox, idx);
                    } else if (val) {
                        const parts = val.split(' ');
                        if (parts.length === 2) {
                            const monthSel = document.getElementById(`time-to-month-${idx}`);
                            const yearSel = document.getElementById(`time-to-year-${idx}`);
                            if (monthSel) monthSel.value = parts[0];
                            if (yearSel) yearSel.value = parts[1];
                        }
                    }
                }
            });
        }

        function getSkillsCheckboxesHTML(section, index) {
            const skills = [
                "Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills",
                "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility",
                "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"
            ];
            return skills.map((skill, i) => `
                <label>
                    <input type="checkbox" name="pre_app_data[${section}][${index}][skills_learnt][]" value="${i + 1}">
                    ${i + 1}. ${skill}
                </label>
            `).join('');
        }

        let educationIndex = {{ count($formal) }};
        function addEducationRow() {
            const tbody = document.getElementById('education-tbody');
            const card = document.createElement('div');
            card.className = 'row-card education-card';
            card.id = `education-card-${educationIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Education Entry #${educationIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                </div>
                <div class="row-card-body education-grid">
                    <div class="field-col">
                        <label>Year Awarded</label>
                        <input type="text" name="pre_app_data[formal_learning][${educationIndex}][year_awarded]" placeholder="e.g. 2024">
                    </div>
                    <div class="field-col">
                        <label>Title of Certification</label>
                        <input type="text" name="pre_app_data[formal_learning][${educationIndex}][title_of_certification]" placeholder="e.g. Cert">
                    </div>
                    <div class="field-col">
                        <label>Level of Award</label>
                        <input type="text" name="pre_app_data[formal_learning][${educationIndex}][level_of_award]" placeholder="e.g. Certificate">
                    </div>
                    <div class="field-col">
                        <label>Awarding Body</label>
                        <input type="text" name="pre_app_data[formal_learning][${educationIndex}][awarding_body]" placeholder="Awarding Body">
                    </div>
                </div>
            `;
            tbody.appendChild(card);
            educationIndex++;
        }

        let employmentIndex = {{ count($jobs) }};
        function addEmploymentRow() {
            const tbody = document.getElementById('employment-tbody');
            const card = document.createElement('div');
            card.className = 'row-card employment-card';
            card.id = `employment-card-${employmentIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Employer Entry #${employmentIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                </div>
                <div class="row-card-body employment-grid">
                    <div class="field-col employer-name">
                        <label>Employer Name</label>
                        <input type="text" name="pre_app_data[experiential_learning][${employmentIndex}][employer_name]" placeholder="e.g. Roche">
                    </div>
                    <div class="field-col contact-address">
                        <label>Contact Address</label>
                        <input type="text" name="pre_app_data[experiential_learning][${employmentIndex}][contact_address]" placeholder="Address">
                    </div>
                    <div class="field-col time-from">
                        <label>From (Month/Year)</label>
                        <div class="month-year-picker">
                            <div class="selects-row" style="display: flex !important; flex-direction: row !important; gap: 8px !important;">
                                <select class="select-month" id="time-from-month-${employmentIndex}" onchange="updateCombinedDate(${employmentIndex}, 'from')">
                                    <option value="">Month</option>
                                    ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].map(m => `<option value="${m}">${m}</option>`).join('')}
                                </select>
                                <select class="select-year" id="time-from-year-${employmentIndex}" onchange="updateCombinedDate(${employmentIndex}, 'from')">
                                    <option value="">Year</option>
                                    ${Array.from({length: 60}, (_, i) => new Date().getFullYear() + 2 - i).map(y => `<option value="${y}">${y}</option>`).join('')}
                                </select>
                            </div>
                            <input type="hidden" name="pre_app_data[experiential_learning][${employmentIndex}][time_from]" id="time-from-hidden-${employmentIndex}">
                        </div>
                    </div>
                    <div class="field-col time-to">
                        <label>To (Month/Year)</label>
                        <div class="month-year-picker">
                            <div class="selects-row" id="to-selects-container-${employmentIndex}" style="display: flex !important; flex-direction: row !important; gap: 8px !important;">
                                <select class="select-month" id="time-to-month-${employmentIndex}" onchange="updateCombinedDate(${employmentIndex}, 'to')">
                                    <option value="">Month</option>
                                    ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].map(m => `<option value="${m}">${m}</option>`).join('')}
                                </select>
                                <select class="select-year" id="time-to-year-${employmentIndex}" onchange="updateCombinedDate(${employmentIndex}, 'to')">
                                    <option value="">Year</option>
                                    ${Array.from({length: 60}, (_, i) => new Date().getFullYear() + 2 - i).map(y => `<option value="${y}">${y}</option>`).join('')}
                                </select>
                            </div>
                            <div id="to-current-display-${employmentIndex}" style="display: none; font-weight: 600; color: #8B1E3F; font-size: 13px; padding: 6px 8px; border: 1px solid #d1d5db; background: #faf9fa; border-radius: 6px; text-align: center;">Current</div>
                            <input type="hidden" name="pre_app_data[experiential_learning][${employmentIndex}][time_to]" id="time-to-hidden-${employmentIndex}">
                            <label style="font-size: 11px; font-weight: normal; margin-top: 4px; display: flex; align-items: center; gap: 4px; color: #4b5563; cursor: pointer; width: auto !important; margin-bottom: 0 !important; text-transform: none; letter-spacing: normal;">
                                <input type="checkbox" id="time-to-current-${employmentIndex}" onclick="toggleCurrentWorkCheckbox(this, ${employmentIndex})" style="width: 13px !important; height: 13px !important; margin: 0 !important; cursor: pointer;">
                                <span>Present</span>
                            </label>
                        </div>
                    </div>
                    <div class="field-col position-held">
                        <label>Position Held</label>
                        <input type="text" name="pre_app_data[experiential_learning][${employmentIndex}][position_held]" placeholder="Position">
                    </div>
                    <div class="field-col job-roles">
                        <label>Job Roles / Performed</label>
                        <textarea name="pre_app_data[experiential_learning][${employmentIndex}][job_roles]" placeholder="Roles / Duties" rows="3"></textarea>
                    </div>
                </div>
            `;
            tbody.appendChild(card);
            employmentIndex++;
        }

        let trainingIndex = {{ count($trainings) }};
        function addTrainingRow() {
            const tbody = document.getElementById('training-tbody');
            const card = document.createElement('div');
            card.className = 'row-card training-card';
            card.id = `training-card-${trainingIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Training Entry #${trainingIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                </div>
                <div class="row-card-body training-grid">
                    <div class="field-col course-name">
                        <label>Course/Training Name</label>
                        <input type="text" name="pre_app_data[training_activities][${trainingIndex}][course_name]" placeholder="Course Title">
                    </div>
                    <div class="field-col location">
                        <label>Location</label>
                        <input type="text" name="pre_app_data[training_activities][${trainingIndex}][location]" placeholder="Location">
                    </div>
                    <div class="field-col date-duration">
                        <label>Date & Duration</label>
                        <input type="text" name="pre_app_data[training_activities][${trainingIndex}][date_duration]" placeholder="e.g. Nov 2024">
                    </div>
                    <div class="field-col activity-type">
                        <label>Activity Type</label>
                        <select name="pre_app_data[training_activities][${trainingIndex}][activity_type]">
                            <option value="Technical">Technical</option>
                            <option value="Managerial">Managerial</option>
                            <option value="Both">Both</option>
                        </select>
                    </div>
                    <div class="field-col skills-learnt">
                        <label>What Have I Learnt? (Skills Checklist)</label>
                        <div class="skills-grid" id="skills-grid-t${trainingIndex}"></div>
                    </div>
                </div>
            `;
            tbody.appendChild(card);
            document.getElementById(`skills-grid-t${trainingIndex}`).innerHTML = getSkillsCheckboxesHTML('training_activities', trainingIndex);
            trainingIndex++;
        }

        let otherSkillIndex = {{ count($otherSkills) }};
        function addOtherSkillRow() {
            const tbody = document.getElementById('other-skills-tbody');
            const card = document.createElement('div');
            card.className = 'row-card other-skills-card';
            card.id = `other-skills-card-${otherSkillIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Other Activity Entry #${otherSkillIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: #ef4444; border-color: #ef4444; color: white;">Remove</button>
                </div>
                <div class="row-card-body other-skills-grid">
                    <div class="field-col other-activities">
                        <label>Other Activity Description</label>
                        <input type="text" name="pre_app_data[other_learning_skills][${otherSkillIndex}][other_activities]" placeholder="Description">
                    </div>
                    <div class="field-col year">
                        <label>Year</label>
                        <input type="text" name="pre_app_data[other_learning_skills][${otherSkillIndex}][year]" placeholder="Year">
                    </div>
                    <div class="field-col skills-learnt">
                        <label>What Have I Learnt? (Skills Checklist)</label>
                        <div class="skills-grid" id="skills-grid-o${otherSkillIndex}"></div>
                    </div>
                </div>
            `;
            tbody.appendChild(card);
            document.getElementById(`skills-grid-o${otherSkillIndex}`).innerHTML = getSkillsCheckboxesHTML('other_learning_skills', otherSkillIndex);
            otherSkillIndex++;
        }

        function openTab(evt, tabName) {
            revealTab(tabName);
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add("active");
            }
        }

        /**
         * Reveal a tab without needing a click event, so validation and error
         * handling can bring the user to the panel holding the problem.
         */
        function revealTab(tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            const tabLinks = document.getElementsByClassName("tab-link");
            for (let i = 0; i < tabLinks.length; i++) {
                tabLinks[i].classList.remove("active");
            }

            const panel = document.getElementById(tabName);
            if (panel) {
                panel.classList.add("active");
                const link = document.querySelector('.tab-link[onclick*="' + tabName + '"]');
                if (link) link.classList.add("active");
            }
        }

        /**
         * A control inside a display:none panel cannot be focused, so the browser
         * silently refuses to submit and shows no message at all - the user clicks
         * Submit and nothing happens. Reveal the offending panel first, then let
         * native validation report against a visible control.
         */
        function focusFirstInvalid(formEl) {
            const invalid = formEl.querySelector(':invalid');
            if (!invalid) return false;

            const panel = invalid.closest('.tab-content');
            if (panel && panel.id) revealTab(panel.id);

            invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { invalid.focus({ preventScroll: true }); } catch (e) { /* non-focusable */ }
            formEl.reportValidity();
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('application-type-select');
            const apelAProgrammeBox = document.getElementById('apel-a-programme-box');
            const apelCCourseBox = document.getElementById('apel-c-course-box');
            const apelAFormBox = document.getElementById('apel-a-form-box');
            const apelCFormBox = document.getElementById('apel-c-form-box');

            const portfolioInput = document.getElementById('portfolio-input');
            const portfolioPreview = document.getElementById('portfolio-preview-list');

            function toggleFields() {
                const reqCardA = document.getElementById('apel-a-requirements-card');
                const reqCardC = document.getElementById('apel-c-requirements-card');

                if (typeSelect.value === 'APEL A') {
                    apelAProgrammeBox.style.display = 'block';
                    apelCCourseBox.style.display = 'none';
                    apelAFormBox.style.display = 'block';
                    apelCFormBox.style.display = 'none';
                    setRequiredState(apelAFormBox, true);
                    setRequiredState(apelCFormBox, false);
                    if (reqCardA) reqCardA.style.display = 'block';
                    if (reqCardC) reqCardC.style.display = 'none';
                } else if (typeSelect.value === 'APEL C') {
                    apelAProgrammeBox.style.display = 'none';
                    apelCCourseBox.style.display = 'block';
                    apelAFormBox.style.display = 'none';
                    apelCFormBox.style.display = 'block';
                    setRequiredState(apelAFormBox, false);
                    setRequiredState(apelCFormBox, true);
                    if (reqCardA) reqCardA.style.display = 'none';
                    if (reqCardC) reqCardC.style.display = 'block';
                } else {
                    apelAProgrammeBox.style.display = 'none';
                    apelCCourseBox.style.display = 'none';
                    apelAFormBox.style.display = 'none';
                    apelCFormBox.style.display = 'none';
                    if (reqCardA) reqCardA.style.display = 'none';
                    if (reqCardC) reqCardC.style.display = 'none';
                }
            }

            function setRequiredState(container, required) {
                const inputs = container.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (required) {
                        // Skip elements inside dynamic row cards
                        if (input.closest('.row-card')) {
                            input.removeAttribute('required');
                            return;
                        }
                        if (input.hasAttribute('data-was-required') || input.tagName === 'SELECT' || input.type === 'text' || input.type === 'date' || input.type === 'number' || input.tagName === 'TEXTAREA' || input.type === 'checkbox') {
                            if (!input.classList.contains('optional')) {
                                input.setAttribute('required', 'required');
                            }
                        }
                    } else {
                        if (input.hasAttribute('required')) {
                            input.setAttribute('data-was-required', 'true');
                            input.removeAttribute('required');
                        }
                    }
                });
            }

            function handleFileSelect(input, previewDiv) {
                previewDiv.innerHTML = '';
                if (input.files.length > 0) {
                    const ul = document.createElement('ul');
                    ul.style.listStyle = 'none';
                    ul.style.padding = '0';
                    ul.style.margin = '4px 0 0 0';
                    for (let i = 0; i < input.files.length; i++) {
                        const file = input.files[i];
                        const li = document.createElement('li');
                        li.style.padding = '3px 0';
                        li.style.display = 'flex';
                        li.style.alignItems = 'center';
                        li.style.gap = '8px';
                        
                        const sizeKB = (file.size / 1024).toFixed(1);
                        li.innerHTML = `📄 <strong style="color: #1f2937;">${file.name}</strong> <span style="color: #6b7280; font-size: 11px;">(${sizeKB} KB)</span>`;
                        ul.appendChild(li);
                    }
                    previewDiv.appendChild(ul);
                }
            }

            typeSelect.addEventListener('change', toggleFields);
            toggleFields();
            window.addEventListener('pageshow', toggleFields);
            setTimeout(toggleFields, 100);
            initMonthYearPickers();

            // Real-time IC & Age validation
            const icInput = document.getElementById('ic-no-input');
            const citizenshipIndicator = document.getElementById('citizenship-indicator');
            const ageInput = document.getElementById('age-input');
            const ageWarning = document.getElementById('age-warning');

            if (icInput && citizenshipIndicator) {
                icInput.addEventListener('input', function() {
                    const value = icInput.value.trim().replace(/-/g, '');
                    if (/^\d{12}$/.test(value)) {
                        citizenshipIndicator.innerHTML = '<span style="color: #10b981;">🇲🇾 Malaysian Citizen verified (Valid IC Format)</span>';
                    } else if (value.length > 0) {
                        citizenshipIndicator.innerHTML = '<span style="color: #ef4444;">❌ Invalid format. APEL A candidates must be Malaysian Citizens with a valid 12-digit IC.</span>';
                    } else {
                        citizenshipIndicator.innerHTML = 'Please enter your 12-digit IC number.';
                    }
                });
                if (icInput.value) icInput.dispatchEvent(new Event('input'));
            }

            if (ageInput && ageWarning) {
                ageInput.addEventListener('input', function() {
                    const age = parseInt(ageInput.value);
                    if (!isNaN(age) && age < 30) {
                        ageWarning.style.display = 'block';
                    } else {
                        ageWarning.style.display = 'none';
                    }
                });
                if (ageInput.value) ageInput.dispatchEvent(new Event('input'));
            }

            const qualInput = document.getElementById('qualification-input');
            const qualWarning = document.getElementById('qualification-warning');

            if (qualInput && qualWarning) {
                qualInput.addEventListener('input', function() {
                    const val = qualInput.value.trim().toLowerCase();
                    if (val.length > 0 && !val.startsWith('diploma')) {
                        qualWarning.style.display = 'block';
                    } else {
                        qualWarning.style.display = 'none';
                    }
                });
                if (qualInput.value) qualInput.dispatchEvent(new Event('input'));
            }

            if (portfolioInput && portfolioPreview) {
                portfolioInput.addEventListener('change', () => handleFileSelect(portfolioInput, portfolioPreview));
            }

            // LocalStorage Auto-Save
            /*
             * Scoped to this application. It previously reused the create page's
             * key, so opening a draft for editing silently overwrote the backup
             * belonging to a half-finished new application.
             */
            const autosaveKey = 'apel_c_autosave_edit_' + '{{ $application->_id }}';
            const form = document.getElementById('apel-application-form');

            form.addEventListener('submit', function(e) {
                const submitType = document.activeElement ? document.activeElement.value : '';

                if (typeSelect.value === 'APEL A' && submitType === 'submit') {
                    const age = parseInt(ageInput.value);
                    if (!isNaN(age) && age < 30) {
                        alert("Alert: APEL A requires candidates to be at least 30 years of age at the time of application.");
                        e.preventDefault();
                        return false;
                    }
                    const icVal = icInput.value.trim().replace(/-/g, '');
                    if (!/^\d{12}$/.test(icVal)) {
                        alert("Alert: APEL A candidates must be Malaysian Citizens with a valid 12-digit Identity Card (IC) number.");
                        e.preventDefault();
                        return false;
                    }
                    const qualVal = qualInput.value.trim().toLowerCase();
                    if (!qualVal.startsWith('diploma')) {
                        alert("Alert: The highest academic qualification for APEL A must start exactly with 'Diploma' (e.g. Diploma in Computer Science).");
                        e.preventDefault();
                        return false;
                    }
                }
                
                if (submitType === 'draft') {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    formData.set('submit_type', 'draft');
                    
                    const saveDraftBtn = form.querySelector('button[value="draft"]');
                    const originalText = saveDraftBtn ? saveDraftBtn.innerHTML : 'Save Draft';
                    if (saveDraftBtn) {
                        saveDraftBtn.disabled = true;
                        saveDraftBtn.innerHTML = 'Saving...';
                    }
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.application_id && form.action.endsWith('/student/applications')) {
                                form.action = `/student/applications/${data.application_id}`;
                                if (!form.querySelector('input[name="_method"]')) {
                                    const methodInput = document.createElement('input');
                                    methodInput.type = 'hidden';
                                    methodInput.name = '_method';
                                    methodInput.value = 'PUT';
                                    form.appendChild(methodInput);
                                }
                            }
                            alert('Draft saved successfully!');
                        } else {
                            alert('Failed to save draft: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error('Error saving draft:', err);
                        alert('An error occurred while saving the draft.');
                    })
                    .finally(() => {
                        if (saveDraftBtn) {
                            saveDraftBtn.disabled = false;
                            saveDraftBtn.innerHTML = originalText;
                        }
                    });
                    return;
                }
                
                // If submitType === 'submit' (Submit Application)
                if (!form.checkValidity()) {
                    e.preventDefault();
                    // reportValidity alone cannot show a message for a control inside a
                    // display:none panel, which is why submitting used to appear to do
                    // nothing at all. Reveal the panel holding the first invalid field
                    // first, then report against a control the browser can focus.
                    focusFirstInvalid(form);
                    return;
                }
                
                // Enforce portfolio file upload validation on final submit
                // NOTE: typeSelect is already declared in the enclosing scope.
                // Re-declaring it here with const put the whole listener body in a
                // temporal dead zone, so the reference above threw on every submit.
                if (typeSelect && typeSelect.value === 'APEL C') {
                    const portfolioPreview = document.getElementById('portfolio-preview-list');
                    const portfolioInput = document.getElementById('portfolio-input');
                    const hasPortfolio = (portfolioPreview && portfolioPreview.querySelectorAll('li').length > 0) || (portfolioInput && portfolioInput.files.length > 0);
                    if (!hasPortfolio) {
                        alert('Please upload your completed Portfolio PDF under Part C.');
                        e.preventDefault();
                        return;
                    }
                }
                
                if (!confirm("Everything in this application will be finalized and cannot be edited after submission. Do you want to submit?")) {
                    e.preventDefault();
                    return;
                }
            });

            function saveFormData() {
                const formData = {};
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.type === 'file' || input.type === 'password' || input.name === '_token' || input.name === 'submit_type' || input.name === '_method') return;
                    if (input.type === 'checkbox') {
                        formData[input.name + '_' + input.value] = input.checked;
                    } else if (input.type === 'radio') {
                        if (input.checked) {
                            formData[input.name] = input.value;
                        }
                    } else {
                        formData[input.name] = input.value;
                    }
                });
                localStorage.setItem(autosaveKey, JSON.stringify(formData));
            }

            form.addEventListener('input', saveFormData);
            form.addEventListener('change', saveFormData);

            // Clear auto-save data on form submit
            form.addEventListener('submit', function() {
                // Cleared only after the server confirms the update; see index.blade.php.
                // Wiping here fired before validation ran and destroyed the student's work.
            });

            function showAutoSaveNotice() {
                const notice = document.getElementById('autosave-notice');
                if (notice) {
                    notice.style.opacity = '1';
                    setTimeout(() => {
                        notice.style.opacity = '0';
                    }, 3000);
                }
            }

            // Auto-save to server database every 2 minutes
            setInterval(function() {
                // Ensure application type is selected before auto-saving
                const typeSelect = document.getElementById('application-type-select');
                if (!typeSelect || !typeSelect.value) return;

                const formData = new FormData(form);
                formData.set('submit_type', 'draft');
                
                // Do not upload files during auto-save to avoid huge payloads
                form.querySelectorAll('input[type="file"]').forEach(fileInput => {
                    formData.delete(fileInput.name);
                    formData.delete(fileInput.name + '[]');
                });

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Draft auto-saved:', data.message);
                        
                        // If we are on the create page and just got the new ID
                        if (data.application_id && form.action.endsWith('/student/applications')) {
                            // Update action to update route
                            form.action = `/student/applications/${data.application_id}`;
                            
                            // Inject _method PUT input
                            if (!form.querySelector('input[name="_method"]')) {
                                const methodInput = document.createElement('input');
                                methodInput.type = 'hidden';
                                methodInput.name = '_method';
                                methodInput.value = 'PUT';
                                form.appendChild(methodInput);
                            }
                        }
                        
                        showAutoSaveNotice();
                    }
                })
                .catch(err => console.error('Auto-save failed:', err));
            }, 120000); // 2 minutes
        });
    </script>
@endsection
