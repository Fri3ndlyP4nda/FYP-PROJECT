@extends('layouts.app')

@section('content')
    <style>
        .form-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--line);
            padding-bottom: 8px;
            flex-wrap: wrap;
        }
        .tab-link {
            border: none;
            background: transparent;
            color: var(--ink-3);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 13.5px;
        }
        .tab-link.active {
            background: var(--maroon);
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
            border: 1px solid var(--line) !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }
        .dynamic-table:not(.language-table) {
            table-layout: fixed !important;
        }
        .dynamic-table th {
            background: var(--surface-sunk) !important;
            color: var(--maroon) !important;
            font-weight: 700 !important;
            padding: 10px 8px !important;
            border: 1px solid var(--line) !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
            line-height: 1.3 !important;
            vertical-align: middle !important;
        }
        .dynamic-table td {
            padding: 10px 12px !important;
            border: 1px solid var(--line) !important;
            vertical-align: middle !important;
        }
        .dynamic-table td input,
        .dynamic-table td select {
            width: 100% !important;
            padding: 8px 10px !important;
            margin-bottom: 0 !important;
            border: 1px solid var(--line-strong) !important;
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
            border: 1px solid var(--line-strong) !important;
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
            border: 1px solid var(--line) !important;
            padding: 8px !important;
            background: var(--surface-sunk) !important;
            border-radius: 6px !important;
            box-sizing: border-box !important;
        }
        .skills-grid label {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 11.5px !important;
            font-weight: 500 !important;
            color: var(--ink-2) !important;
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
            border: 1px solid var(--line-strong) !important;
            background-color: #ffffff !important;
            margin-bottom: 0 !important;
            box-sizing: border-box !important;
        }

        .referee-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            background: var(--surface-sunk);
        }
        .referee-card h4 {
            margin-top: 0;
            color: var(--maroon);
            font-weight: 600;
            font-size: 14.5px;
            border-bottom: 1px solid var(--line);
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
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .row-card-header {
            background: #fafafb;
            border-bottom: 1px solid var(--line);
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--maroon);
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
            color: var(--ink-2);
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
            border: 1px solid var(--line-strong) !important;
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
            background: var(--surface-sunk);
            padding: 12px;
            border: 1px solid var(--line);
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
            color: var(--ink-2) !important;
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
                <span class="section-pill">New Submission</span>
                <h2>Submit APEL Application</h2>
                <p class="muted page-hero-text">
                    Complete the official APEL application forms below.
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

                <form method="POST" action="{{ route('student.applications.store') }}" enctype="multipart/form-data" id="apel-application-form">
                    @csrf

                    <div class="form-row">
                        <div>
                            <label for="application-type-select">Application Type</label>
                            <select name="application_type" id="application-type-select" required>
                                <option value="">-- Select --</option>
                                <option value="APEL A" {{ old('application_type') == 'APEL A' ? 'selected' : '' }}>APEL A</option>
                                <option value="APEL C" {{ old('application_type') == 'APEL C' ? 'selected' : '' }}>APEL C</option>
                            </select>
                            <x-field-error name="application_type" />
                        </div>

                        <div id="apel-a-programme-box">
                            <label for="f-program-applied">Programme Applied</label>
                            <select name="program_applied" id="f-program-applied">
                                <option value="">-- Select Master Programme --</option>
                                @foreach ($programmes as $programme)
                                    <option value="{{ $programme->name }}"
                                        {{ old('program_applied') == $programme->name ? 'selected' : '' }}>
                                        {{ $programme->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-field-error name="program_applied" />
                        </div>

                        <div id="apel-c-course-box" style="display: none;">
                            <label for="f-course-id">APEL C Course</label>
                            <select name="course_id" id="f-course-id">
                                <option value="">-- Select Course --</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->_id }}"
                                        {{ old('course_id') == $course->_id ? 'selected' : '' }}>
                                        {{ $course->course_name }} ({{ $course->course_code }})
                                    </option>
                                @endforeach
                            </select>
                            <x-field-error name="course_id" />
                        </div>
                    </div>

                    {{-- APEL A Internal Form --}}
                    <div id="apel-a-form-box" style="display: none;">
                        <hr>
                        <h3>APEL A Internal Application Form</h3>

                        <label for="ic-no-input">Identity Card (IC) No.</label>
                        <input type="text" name="ic_no" id="ic-no-input" value="{{ old('ic_no') }}" placeholder="Example: 951020-10-5033" style="margin-bottom: 4px !important;">
                        <x-field-error name="ic_no" />
                        <div id="citizenship-indicator" style="font-size: 12px; font-weight: 600; margin-bottom: 15px; display: block;">
                            Please enter your 12-digit IC number.
                        </div>

                        <label for="age-input">Age</label>
                        <input type="number" name="age" id="age-input" value="{{ old('age') }}" min="18" max="100" placeholder="Example: 25" style="margin-bottom: 4px !important;">
                        <x-field-error name="age" />
                        <div id="age-warning" style="font-size: 12px; color: var(--bad); font-weight: 600; margin-bottom: 15px; display: none;">
                            ⚠️ Alert: APEL A for Master level access requires candidates to be at least 30 years of age.
                        </div>

                        <label for="f-university-name">Name of University (Highest Qualification)</label>
                        <input type="text" name="university_name" value="{{ old('university_name') }}" placeholder="Example: Universiti Teknologi Malaysia" id="f-university-name">
                        <x-field-error name="university_name" />

                        <label for="f-company-name">Name of Company (Current Employment)</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Example: Google Inc." id="f-company-name">
                        <x-field-error name="company_name" />

                        <label for="qualification-input">Highest Academic Qualification</label>
                        <input type="text" name="highest_qualification" id="qualification-input" value="{{ old('highest_qualification') }}"
                            placeholder="Example: Diploma in Computer Science" style="margin-bottom: 4px !important;">
                        <x-field-error name="highest_qualification" />
                        <div id="qualification-warning" style="font-size: 12px; color: var(--bad); font-weight: 600; margin-bottom: 15px; display: none;">
                            ⚠️ Alert: The highest qualification for APEL A must start exactly with "Diploma" (e.g., Diploma in Computer Science).
                        </div>

                        <label for="f-current-job">Current Job / Position</label>
                        <input type="text" name="current_job" value="{{ old('current_job') }}"
                            placeholder="Example: IT Executive / Software Developer" id="f-current-job">
                        <x-field-error name="current_job" />

                        <label for="f-working-experience-years">Years of Working Experience</label>
                        <input type="number" name="working_experience_years" value="{{ old('working_experience_years') }}"
                            min="0" placeholder="Example: 5" id="f-working-experience-years">
                        <x-field-error name="working_experience_years" />

                        <label for="f-working-experience-details">Relevant Working Experience</label>
                        <textarea name="working_experience_details" rows="4"
                            placeholder="Briefly describe your working experience related to the selected programme." id="f-working-experience-details">{{ old('working_experience_details') }}</textarea>
                        <x-field-error name="working_experience_details" />

                        <label for="f-reason-applying">Reason for Applying APEL A</label>
                        <textarea name="reason_applying" rows="4" placeholder="Explain why you are applying through APEL A." id="f-reason-applying">{{ old('reason_applying') }}</textarea>
                        <x-field-error name="reason_applying" />
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
                            <label for="f-target-semester">Target Semester</label>
                            <select name="target_semester" id="f-target-semester">
                                <option value="">-- Select Semester --</option>
                                <option value="Semester 1" {{ old('target_semester') == 'Semester 1' ? 'selected' : '' }}>Semester 1</option>
                                <option value="Semester 2" {{ old('target_semester') == 'Semester 2' ? 'selected' : '' }}>Semester 2</option>
                                <option value="Semester 3" {{ old('target_semester') == 'Semester 3' ? 'selected' : '' }}>Semester 3</option>
                            </select>
                            <x-field-error name="target_semester" />
                            -->

                            <h4 style="color: var(--maroon); margin-top: 15px; margin-bottom: 10px;">PART A: PERSONAL PARTICULARS</h4>
                            <label for="f-pre-app-data-personal-particulars-name">Full Name (As per IC)</label>
                            <input type="text" name="pre_app_data[personal_particulars][name]" value="{{ old('pre_app_data.personal_particulars.name', auth()- id="f-pre-app-data-personal-particulars-name">
                            <x-field-error name="pre_app_data.personal_particulars.name" />user()->name) }}" required>

                            <label for="f-pre-app-data-personal-particulars-matric-no">Matric No.</label>
                            <input type="text" name="pre_app_data[personal_particulars][matric_no]" value="{{ old('pre_app_data.personal_particulars.matric_no') }}" placeholder="e.g. MEC244062" id="f-pre-app-data-personal-particulars-matric-no">
                            <x-field-error name="pre_app_data.personal_particulars.matric_no" />

                            <label for="f-pre-app-data-personal-particulars-ic-no">Identity Card No.</label>
                            <input type="text" name="pre_app_data[personal_particulars][ic_no]" value="{{ old('pre_app_data.personal_particulars.ic_no') }}" placeholder="e.g. 851020105033" required id="f-pre-app-data-personal-particulars-ic-no">
                            <x-field-error name="pre_app_data.personal_particulars.ic_no" />

                            <label for="f-pre-app-data-personal-particulars-highest-qualification">Highest Academic Qualification</label>
                            <select name="pre_app_data[personal_particulars][highest_qualification]" required id="f-pre-app-data-personal-particulars-highest-qualification">
                                <option value="">-- Select Qualification --</option>
                                <option value="PhD">PhD / Doctoral Degree</option>
                                <option value="Master">Master's Degree</option>
                                <option value="Bachelor">Bachelor's Degree</option>
                                <option value="Diploma">Diploma (Minimum Eligibility)</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Other">Other</option>
                            </select>
                            <x-field-error name="pre_app_data.personal_particulars.highest_qualification" />

                            <h4 style="color: var(--maroon); margin-top: 20px; margin-bottom: 10px;">PART B (i): FORMAL LEARNING (CERTIFICATED EDUCATION)</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addEducationRow()">+ Add Education</button>
                            </div>
                            <div class="cards-container" id="education-tbody">
                                <div class="row-card education-card" id="education-card-0">
                                    <div class="row-card-header">
                                        <span>Education Entry #1</span>
                                    </div>
                                    <div class="row-card-body education-grid">
                                        <div class="field-col">
                                            <label for="f-pre-app-data-formal-learning-0-year-awarded">Year Awarded</label>
                                            <input type="text" name="pre_app_data[formal_learning][0][year_awarded]" placeholder="e.g. 2024" id="f-pre-app-data-formal-learning-0-year-awarded">
                                            <x-field-error name="pre_app_data.formal_learning.0.year_awarded" />
                                        </div>
                                        <div class="field-col">
                                            <label for="f-pre-app-data-formal-learning-0-title-of-certification">Title of Certification</label>
                                            <input type="text" name="pre_app_data[formal_learning][0][title_of_certification]" placeholder="e.g. Certificate of Cloud Security Knowledge" id="f-pre-app-data-formal-learning-0-title-of-certification">
                                            <x-field-error name="pre_app_data.formal_learning.0.title_of_certification" />
                                        </div>
                                        <div class="field-col">
                                            <label for="f-pre-app-data-formal-learning-0-level-of-award">Level of Award</label>
                                            <input type="text" name="pre_app_data[formal_learning][0][level_of_award]" placeholder="e.g. Certificate" id="f-pre-app-data-formal-learning-0-level-of-award">
                                            <x-field-error name="pre_app_data.formal_learning.0.level_of_award" />
                                        </div>
                                        <div class="field-col">
                                            <label for="f-pre-app-data-formal-learning-0-awarding-body">Awarding Body</label>
                                            <input type="text" name="pre_app_data[formal_learning][0][awarding_body]" placeholder="e.g. Cloud Security Alliance" id="f-pre-app-data-formal-learning-0-awarding-body">
                                            <x-field-error name="pre_app_data.formal_learning.0.awarding_body" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 2: Experience & Training --}}
                        <div id="tab-experience" class="tab-content">
                            <h4 style="color: var(--maroon); margin-bottom: 10px;">PART B (ii): EXPERIENTIAL LEARNING (EMPLOYMENT HISTORY)</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addEmploymentRow()">+ Add Employer</button>
                            </div>
                            <div class="cards-container" id="employment-tbody">
                                <div class="row-card employment-card" id="employment-card-0">
                                    <div class="row-card-header">
                                        <span>Employer Entry #1</span>
                                    </div>
                                    <div class="row-card-body employment-grid">
                                        <div class="field-col employer-name">
                                            <label for="f-pre-app-data-experiential-learning-0-employer-name">Employer Name</label>
                                            <input type="text" name="pre_app_data[experiential_learning][0][employer_name]" placeholder="e.g. Roche" id="f-pre-app-data-experiential-learning-0-employer-name">
                                            <x-field-error name="pre_app_data.experiential_learning.0.employer_name" />
                                        </div>
                                        <div class="field-col contact-address">
                                            <label for="f-pre-app-data-experiential-learning-0-contact-address">Contact Address</label>
                                            <input type="text" name="pre_app_data[experiential_learning][0][contact_address]" placeholder="Address" id="f-pre-app-data-experiential-learning-0-contact-address">
                                            <x-field-error name="pre_app_data.experiential_learning.0.contact_address" />
                                        </div>
                                        <div class="field-col time-from">
                                            <label>From (Month/Year)</label>
                                            <div class="month-year-picker">
                                                <div class="selects-row" style="display: flex !important; flex-direction: row !important; gap: 8px !important;">
                                                    <select class="select-month" id="time-from-month-0" onchange="updateCombinedDate(0, 'from')">
                                                        <option value="">Month</option>
                                                        @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $m)
                                                            <option value="{{ $m }}">{{ $m }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select class="select-year" id="time-from-year-0" onchange="updateCombinedDate(0, 'from')">
                                                        <option value="">Year</option>
                                                        @for($y = date('Y') + 2; $y >= 1970; $y--)
                                                            <option value="{{ $y }}">{{ $y }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <input type="hidden" name="pre_app_data[experiential_learning][0][time_from]" value="" id="time-from-hidden-0">
                                            </div>
                                        </div>
                                        <div class="field-col time-to">
                                            <label>To (Month/Year)</label>
                                            <div class="month-year-picker">
                                                <div class="selects-row" id="to-selects-container-0" style="display: flex !important; flex-direction: row !important; gap: 8px !important;">
                                                    <select class="select-month" id="time-to-month-0" onchange="updateCombinedDate(0, 'to')">
                                                        <option value="">Month</option>
                                                        @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $m)
                                                            <option value="{{ $m }}">{{ $m }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select class="select-year" id="time-to-year-0" onchange="updateCombinedDate(0, 'to')">
                                                        <option value="">Year</option>
                                                        @for($y = date('Y') + 2; $y >= 1970; $y--)
                                                            <option value="{{ $y }}">{{ $y }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div id="to-current-display-0" style="display: none; font-weight: 600; color: var(--maroon); font-size: 13px; padding: 6px 8px; border: 1px solid var(--line-strong); background: var(--surface-sunk); border-radius: 6px; text-align: center;">Current</div>
                                                <input type="hidden" name="pre_app_data[experiential_learning][0][time_to]" value="" id="time-to-hidden-0">
                                                <label style="font-size: 11px; font-weight: normal; margin-top: 4px; display: flex; align-items: center; gap: 4px; color: var(--ink-2); cursor: pointer; width: auto !important; margin-bottom: 0 !important; text-transform: none; letter-spacing: normal;">
                                                    <input type="checkbox" 
                                                           id="time-to-current-0" 
                                                           onclick="toggleCurrentWorkCheckbox(this, 0)" 
                                                           style="width: 13px !important; height: 13px !important; margin: 0 !important; cursor: pointer;">
                                                    <span>Present</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="field-col position-held">
                                            <label for="f-pre-app-data-experiential-learning-0-position-held">Position Held</label>
                                            <input type="text" name="pre_app_data[experiential_learning][0][position_held]" placeholder="Position" id="f-pre-app-data-experiential-learning-0-position-held">
                                            <x-field-error name="pre_app_data.experiential_learning.0.position_held" />
                                        </div>
                                        <div class="field-col job-roles">
                                            <label for="f-pre-app-data-experiential-learning-0-job-roles">Job Roles / Performed</label>
                                            <textarea name="pre_app_data[experiential_learning][0][job_roles]" placeholder="Roles / Duties" rows="3" id="f-pre-app-data-experiential-learning-0-job-roles"></textarea>
                                            <x-field-error name="pre_app_data.experiential_learning.0.job_roles" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4 style="color: var(--maroon); margin-top: 20px; margin-bottom: 10px;">TRAINING ACTIVITIES</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addTrainingRow()">+ Add Training Activity</button>
                            </div>
                            <div class="cards-container" id="training-tbody">
                                <div class="row-card training-card" id="training-card-0">
                                    <div class="row-card-header">
                                        <span>Training Entry #1</span>
                                    </div>
                                    <div class="row-card-body training-grid">
                                        <div class="field-col course-name">
                                            <label for="f-pre-app-data-training-activities-0-course-name">Course/Training Name</label>
                                            <input type="text" name="pre_app_data[training_activities][0][course_name]" placeholder="Course Title" id="f-pre-app-data-training-activities-0-course-name">
                                            <x-field-error name="pre_app_data.training_activities.0.course_name" />
                                        </div>
                                        <div class="field-col location">
                                            <label for="f-pre-app-data-training-activities-0-location">Location</label>
                                            <input type="text" name="pre_app_data[training_activities][0][location]" placeholder="Location" id="f-pre-app-data-training-activities-0-location">
                                            <x-field-error name="pre_app_data.training_activities.0.location" />
                                        </div>
                                        <div class="field-col date-duration">
                                            <label for="f-pre-app-data-training-activities-0-date-duration">Date & Duration</label>
                                            <input type="text" name="pre_app_data[training_activities][0][date_duration]" placeholder="e.g. Nov 2024 (15.2 hours)" id="f-pre-app-data-training-activities-0-date-duration">
                                            <x-field-error name="pre_app_data.training_activities.0.date_duration" />
                                        </div>
                                        <div class="field-col activity-type">
                                            <label for="f-pre-app-data-training-activities-0-activity-type">Activity Type</label>
                                            <select name="pre_app_data[training_activities][0][activity_type]" id="f-pre-app-data-training-activities-0-activity-type">
                                                <option value="Technical">Technical</option>
                                                <option value="Managerial">Managerial</option>
                                                <option value="Both">Both</option>
                                            </select>
                                            <x-field-error name="pre_app_data.training_activities.0.activity_type" />
                                        </div>
                                        <div class="field-col skills-learnt">
                                            <label>What Have I Learnt? (Skills Checklist)</label>
                                            <div class="skills-grid" id="skills-grid-t0"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 3: Skills & Languages --}}
                        <div id="tab-skills" class="tab-content">
                            <h4 style="color: var(--maroon); margin-bottom: 10px;">PART B (iii): OTHER LEARNING SKILLS / ACTIVITIES</h4>
                            <div class="table-action-btn-row">
                                <button type="button" class="btn btn-sm" onclick="addOtherSkillRow()">+ Add Activity</button>
                            </div>
                            <div class="cards-container" id="other-skills-tbody">
                                <div class="row-card other-skills-card" id="other-skills-card-0">
                                    <div class="row-card-header">
                                        <span>Other Activity Entry #1</span>
                                    </div>
                                    <div class="row-card-body other-skills-grid">
                                        <div class="field-col other-activities">
                                            <label for="f-pre-app-data-other-learning-skills-0-other-activities">Other Activity Description</label>
                                            <input type="text" name="pre_app_data[other_learning_skills][0][other_activities]" placeholder="hobbies, community services, etc." id="f-pre-app-data-other-learning-skills-0-other-activities">
                                            <x-field-error name="pre_app_data.other_learning_skills.0.other_activities" />
                                        </div>
                                        <div class="field-col year">
                                            <label for="f-pre-app-data-other-learning-skills-0-year">Year</label>
                                            <input type="text" name="pre_app_data[other_learning_skills][0][year]" placeholder="e.g. 2025" id="f-pre-app-data-other-learning-skills-0-year">
                                            <x-field-error name="pre_app_data.other_learning_skills.0.year" />
                                        </div>
                                        <div class="field-col skills-learnt">
                                            <label>What Have I Learnt? (Skills Checklist)</label>
                                            <div class="skills-grid" id="skills-grid-o0"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4 style="color: var(--maroon); margin-top: 20px; margin-bottom: 10px;">PART B (iv): LANGUAGE SKILLS</h4>
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
                                    @foreach (['Bahasa Malaysia' => 'bm', 'English Language' => 'en', 'Mandarin/Chinese' => 'zh'] as $name => $key)
                                        <tr>
                                            <td>
                                                {{ $name }}
                                                <input type="hidden" name="pre_app_data[language_skills][{{ $loop->index }}][language]" value="{{ $name }}">
                                            </td>
                                            @foreach (['listening', 'reading', 'speaking', 'writing'] as $skill)
                                                @for ($i = 1; $i <= 4; $i++)
                                                    <td>
                                                        <input type="radio" name="pre_app_data[language_skills][{{ $parentLoop = $loop->parent->index }}][{{ $skill }}]" value="{{ $i }}" {{ $i == 3 ? 'checked' : '' }} required>
                                                    </td>
                                                @endfor
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div style="font-size: 11px; color: var(--ink-3); margin-top: -10px; margin-bottom: 20px;">
                                Scale Competency - 1: Poor; 2: Average; 3: Good; 4: Excellent
                            </div>
                        </div>

                        {{-- TAB 4: Referees & Self-Assessment --}}
                        <div id="tab-referees" class="tab-content">
                            <h4 style="color: var(--maroon); margin-bottom: 10px;">PART C (ii): REFEREES (Relevant to Work Situation)</h4>
                            <div class="referee-card">
                                <h4>Referee 1</h4>
                                <div class="referee-grid">
                                    <div>
                                        <label for="f-pre-app-data-referees-0-referee-name">Name</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_name]" placeholder="Full Name" required id="f-pre-app-data-referees-0-referee-name">
                                        <x-field-error name="pre_app_data.referees.0.referee_name" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-0-referee-position">Position</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_position]" placeholder="Position/Designation" required id="f-pre-app-data-referees-0-referee-position">
                                        <x-field-error name="pre_app_data.referees.0.referee_position" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-0-referee-organisation">Organisation</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_organisation]" placeholder="Organisation" required id="f-pre-app-data-referees-0-referee-organisation">
                                        <x-field-error name="pre_app_data.referees.0.referee_organisation" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-0-referee-phone-office">Office Phone</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_phone_office]" placeholder="Office No." id="f-pre-app-data-referees-0-referee-phone-office">
                                        <x-field-error name="pre_app_data.referees.0.referee_phone_office" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-0-referee-phone-mobile">Mobile Phone</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_phone_mobile]" placeholder="Mobile No." required id="f-pre-app-data-referees-0-referee-phone-mobile">
                                        <x-field-error name="pre_app_data.referees.0.referee_phone_mobile" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-0-referee-email">Email Address</label>
                                        <input type="email" name="pre_app_data[referees][0][referee_email]" placeholder="email@address.com" required id="f-pre-app-data-referees-0-referee-email">
                                        <x-field-error name="pre_app_data.referees.0.referee_email" />
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <label for="f-pre-app-data-referees-0-referee-relationship">Relationship</label>
                                        <input type="text" name="pre_app_data[referees][0][referee_relationship]" placeholder="e.g. Ex-Supervisor" required id="f-pre-app-data-referees-0-referee-relationship">
                                        <x-field-error name="pre_app_data.referees.0.referee_relationship" />
                                    </div>
                                </div>
                            </div>

                            <div class="referee-card">
                                <h4>Referee 2</h4>
                                <div class="referee-grid">
                                    <div>
                                        <label for="f-pre-app-data-referees-1-referee-name">Name</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_name]" placeholder="Full Name" required id="f-pre-app-data-referees-1-referee-name">
                                        <x-field-error name="pre_app_data.referees.1.referee_name" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-1-referee-position">Position</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_position]" placeholder="Position/Designation" required id="f-pre-app-data-referees-1-referee-position">
                                        <x-field-error name="pre_app_data.referees.1.referee_position" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-1-referee-organisation">Organisation</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_organisation]" placeholder="Organisation" required id="f-pre-app-data-referees-1-referee-organisation">
                                        <x-field-error name="pre_app_data.referees.1.referee_organisation" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-1-referee-phone-office">Office Phone</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_phone_office]" placeholder="Office No." id="f-pre-app-data-referees-1-referee-phone-office">
                                        <x-field-error name="pre_app_data.referees.1.referee_phone_office" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-1-referee-phone-mobile">Mobile Phone</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_phone_mobile]" placeholder="Mobile No." required id="f-pre-app-data-referees-1-referee-phone-mobile">
                                        <x-field-error name="pre_app_data.referees.1.referee_phone_mobile" />
                                    </div>
                                    <div>
                                        <label for="f-pre-app-data-referees-1-referee-email">Email Address</label>
                                        <input type="email" name="pre_app_data[referees][1][referee_email]" placeholder="email@address.com" required id="f-pre-app-data-referees-1-referee-email">
                                        <x-field-error name="pre_app_data.referees.1.referee_email" />
                                    </div>
                                    <div style="grid-column: span 2;">
                                        <label for="f-pre-app-data-referees-1-referee-relationship">Relationship</label>
                                        <input type="text" name="pre_app_data[referees][1][referee_relationship]" placeholder="e.g. Manager" required id="f-pre-app-data-referees-1-referee-relationship">
                                        <x-field-error name="pre_app_data.referees.1.referee_relationship" />
                                    </div>
                                </div>
                            </div>

                            <h4 style="color: var(--maroon); margin-top: 25px; margin-bottom: 10px;">APEL (C) SELF-ASSESSMENT FOR LEARNERS</h4>
                            <p style="font-size:12.5px; color:#5b626a; line-height:1.4; margin-bottom:15px;">
                                For each Course Learning Outcome (CLO), describe how you have learned this outcome through your former studies or working career.
                            </p>

                            <label for="f-self-assessment-clo-descriptions-clo1"><strong>CLO1:</strong> Analyse IT information security governance, risk management frameworks, policies, and standards.</label>
                            <textarea name="self_assessment[clo_descriptions][clo1]" rows="3" placeholder="Describe your experience/certifications related to CLO1..." required id="f-self-assessment-clo-descriptions-clo1"></textarea>
                            <x-field-error name="self_assessment.clo_descriptions.clo1" />

                            <label for="f-self-assessment-clo-descriptions-clo2"><strong>CLO2:</strong> Evaluate applications of security and management, providing justifications based on fundamental concepts.</label>
                            <textarea name="self_assessment[clo_descriptions][clo2]" rows="3" placeholder="Describe your experience/certifications related to CLO2..." required id="f-self-assessment-clo-descriptions-clo2"></textarea>
                            <x-field-error name="self_assessment.clo_descriptions.clo2" />

                            <label for="f-self-assessment-clo-descriptions-clo3"><strong>CLO3:</strong> Complete the cycle of risk identification, analysis, assessment, and control of security management systems.</label>
                            <textarea name="self_assessment[clo_descriptions][clo3]" rows="3" placeholder="Describe your experience/certifications related to CLO3..." required id="f-self-assessment-clo-descriptions-clo3"></textarea>
                            <x-field-error name="self_assessment.clo_descriptions.clo3" />

                            <label for="f-self-assessment-clo-descriptions-clo4"><strong>CLO4:</strong> Construct detailed organisation-wide security plans/policies, and measure safeguards using appropriate tools.</label>
                            <textarea name="self_assessment[clo_descriptions][clo4]" rows="3" placeholder="Describe your experience/certifications related to CLO4..." required id="f-self-assessment-clo-descriptions-clo4"></textarea>
                            <x-field-error name="self_assessment.clo_descriptions.clo4" />
                        </div>

                        {{-- TAB 5: Uploads & Declaration --}}
                        <div id="tab-declaration" class="tab-content">
                            <h4 style="color: var(--maroon); margin-bottom: 10px;">PART C: PORTFOLIO & DECLARATION</h4>
                            
                            <div style="background: var(--surface-sunk); border: 1px solid var(--line); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                                <h5 style="color: var(--maroon); margin-top: 0; margin-bottom: 8px; font-weight: 700; font-size: 13.5px;">📌 PORTFOLIO SUBMISSION INSTRUCTIONS</h5>
                                <p style="font-size: 12.5px; color: var(--ink-2); line-height: 1.5; margin-bottom: 0;">
                                    Please upload your completed <strong>APEL (C) Portfolio Submission Form PDF</strong>. 
                                    This single compiled document must include:
                                    <br>• The **Self-Assessment Essay** (minimum 500 words) addressing all Course Learning Outcomes (CLOs).
                                    <br>• Your **detailed CV / Resume**.
                                    <br>• All **supporting documents & evidence** (certificates, award letters, transcripts, etc.) combined at the end of the document.
                                </p>
                            </div>

                            <label for="portfolio-input"><strong>Upload Complete Portfolio PDF</strong> <span style="color: var(--bad);">*</span></label>
                            <input type="file" name="portfolio_file[]" id="portfolio-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <x-field-error name="portfolio_file" />
                            <div id="portfolio-preview-list" style="margin-top: 8px; font-size: 13px; color: var(--ink-2);"></div>
                            <small style="display:block; margin-top:5px; color:var(--ink-3); margin-bottom:15px;">
                                Allowed format: PDF, JPG, JPEG, PNG, DOC, DOCX. Maximum size: 5MB per file.
                            </small>

                            <h4 style="color: var(--maroon); margin-top: 25px; margin-bottom: 10px;">PART D: SELF-DECLARATION</h4>
                            <div style="background: var(--surface-sunk); border: 1px solid #faebef; padding: 14px; border-radius: 12px; margin-bottom: 15px;">
                                <label style="font-weight: 400; display: flex; align-items: flex-start; gap: 8px; font-size: 13px; line-height: 1.5; cursor: pointer; color: var(--ink-2);">
                                    <input type="checkbox" name="pre_app_data[self_declaration][confirmed]" value="1" required style="width: auto; margin-top: 4px;">
                                    <x-field-error name="pre_app_data.self_declaration.confirmed" />
                                    <span>
                                        I hereby declare that all of the information/documents provided to support this application are authentic, true and accurate. I fully understand that the UTM reserves the right to reject my application if proven otherwise.
                                    </span>
                                </label>
                            </div>

                            <label for="f-pre-app-data-self-declaration-name-as-per-ic">Name (As per IC)</label>
                            <input type="text" name="pre_app_data[self_declaration][name_as_per_ic]" placeholder="Full Name as per IC" required id="f-pre-app-data-self-declaration-name-as-per-ic">
                            <x-field-error name="pre_app_data.self_declaration.name_as_per_ic" />

                            <label for="f-pre-app-data-self-declaration-date-declared">Date Declared</label>
                            <input type="date" name="pre_app_data[self_declaration][date_declared]" value="{{ date('Y-m-d') }}" required id="f-pre-app-data-self-declaration-date-declared">
                            <x-field-error name="pre_app_data.self_declaration.date_declared" />
                        </div>
                    </div>

                    <div class="form-submit-row" style="display: flex; justify-content: flex-end; align-items: center; gap: 8px;">
                        <span id="autosave-notice" style="font-size: 12px; color: var(--good); opacity: 0; transition: opacity 0.3s; margin-right: auto; font-weight: 500;">✓ Draft saved automatically</span>
                        <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="submit_type" value="draft" class="btn btn-secondary" formnovalidate>Save Draft</button>
                        <button type="submit" name="submit_type" value="submit" class="btn" id="submit-application-btn">Submit Application</button>
                    </div>
                </form>
            </div>

            <aside class="info-side-card">
                <!-- APEL.A T-7 Requirements -->
                <div id="apel-a-requirements-card" style="background: var(--surface-sunk); border: 1px solid rgba(139, 30, 63, 0.15); border-radius: 12px; padding: 16px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03); display: none;">
                    <span style="font-size: 10px; font-weight: 700; color: var(--maroon); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">APEL.A T-7 Requirements</span>
                    <h4 style="margin-top: 0; margin-bottom: 8px; color: #30030f; font-size: 14px; font-weight: 700;">Basic Access Eligibility</h4>
                    <ul class="check-list" style="margin-bottom: 0; font-size: 12.5px; color: var(--ink-2); line-height: 1.5; padding-left: 15px;">
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
                    <ul class="check-list" style="margin-bottom: 0; font-size: 12.5px; color: var(--ink-2); line-height: 1.5; padding-left: 15px;">
                        <li><strong>Hold at least a Diploma</strong> qualification.</li>
                        <li><strong>At least 3 years work experience</strong> in a related field.</li>
                        <li><strong>Professional certificates</strong> must be valid within 5 years.</li>
                    </ul>
                </div>

                <span class="side-label">Submission Guide</span>
                <h3>Before you submit</h3>

                <ul class="check-list">
                    <li>Select the correct APEL application type.</li>
                    <li>For APEL A, choose your Master programme.</li>
                    <li>For APEL C, choose the course you want to apply for credit.</li>
                    <li>Complete the internal application form carefully.</li>
                    <li>For APEL C, make sure your Self-Assessment report has at least 500 words.</li>
                </ul>

                <div class="tip-box">
                    <strong>Tip</strong>
                    <p>
                        Write clear details. The Faculty Academic Office and advisor will use this information during eligibility review.
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

        let educationIndex = 1;
        function addEducationRow() {
            const tbody = document.getElementById('education-tbody');
            const card = document.createElement('div');
            card.className = 'row-card education-card';
            card.id = `education-card-${educationIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Education Entry #${educationIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: var(--bad); border-color: var(--bad); color: white;">Remove</button>
                </div>
                <div class="row-card-body education-grid">
                    <div class="field-col">
                        <label for="f-formal-learning-${educationIndex}-year-awarded">Year Awarded</label>
                        <input id="f-formal-learning-${educationIndex}-year-awarded" type="text" name="pre_app_data[formal_learning][${educationIndex}][year_awarded]" placeholder="e.g. 2024">
                    </div>
                    <div class="field-col">
                        <label for="f-formal-learning-${educationIndex}-title-of-certification">Title of Certification</label>
                        <input id="f-formal-learning-${educationIndex}-title-of-certification" type="text" name="pre_app_data[formal_learning][${educationIndex}][title_of_certification]" placeholder="e.g. Cert">
                    </div>
                    <div class="field-col">
                        <label for="f-formal-learning-${educationIndex}-level-of-award">Level of Award</label>
                        <input id="f-formal-learning-${educationIndex}-level-of-award" type="text" name="pre_app_data[formal_learning][${educationIndex}][level_of_award]" placeholder="e.g. Certificate">
                    </div>
                    <div class="field-col">
                        <label for="f-formal-learning-${educationIndex}-awarding-body">Awarding Body</label>
                        <input id="f-formal-learning-${educationIndex}-awarding-body" type="text" name="pre_app_data[formal_learning][${educationIndex}][awarding_body]" placeholder="Awarding Body">
                    </div>
                </div>
            `;
            tbody.appendChild(card);
            educationIndex++;
        }

        let employmentIndex = 1;
        function addEmploymentRow() {
            const tbody = document.getElementById('employment-tbody');
            const card = document.createElement('div');
            card.className = 'row-card employment-card';
            card.id = `employment-card-${employmentIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Employer Entry #${employmentIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: var(--bad); border-color: var(--bad); color: white;">Remove</button>
                </div>
                <div class="row-card-body employment-grid">
                    <div class="field-col employer-name">
                        <label for="f-experiential-learning-${employmentIndex}-employer-name">Employer Name</label>
                        <input id="f-experiential-learning-${employmentIndex}-employer-name" type="text" name="pre_app_data[experiential_learning][${employmentIndex}][employer_name]" placeholder="e.g. Roche">
                    </div>
                    <div class="field-col contact-address">
                        <label for="f-experiential-learning-${employmentIndex}-contact-address">Contact Address</label>
                        <input id="f-experiential-learning-${employmentIndex}-contact-address" type="text" name="pre_app_data[experiential_learning][${employmentIndex}][contact_address]" placeholder="Address">
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
                            <div id="to-current-display-${employmentIndex}" style="display: none; font-weight: 600; color: var(--maroon); font-size: 13px; padding: 6px 8px; border: 1px solid var(--line-strong); background: var(--surface-sunk); border-radius: 6px; text-align: center;">Current</div>
                            <input type="hidden" name="pre_app_data[experiential_learning][${employmentIndex}][time_to]" id="time-to-hidden-${employmentIndex}">
                            <label style="font-size: 11px; font-weight: normal; margin-top: 4px; display: flex; align-items: center; gap: 4px; color: var(--ink-2); cursor: pointer; width: auto !important; margin-bottom: 0 !important; text-transform: none; letter-spacing: normal;">
                                <input type="checkbox" id="time-to-current-${employmentIndex}" onclick="toggleCurrentWorkCheckbox(this, ${employmentIndex})" style="width: 13px !important; height: 13px !important; margin: 0 !important; cursor: pointer;">
                                <span>Present</span>
                            </label>
                        </div>
                    </div>
                    <div class="field-col position-held">
                        <label for="f-experiential-learning-${employmentIndex}-position-held">Position Held</label>
                        <input id="f-experiential-learning-${employmentIndex}-position-held" type="text" name="pre_app_data[experiential_learning][${employmentIndex}][position_held]" placeholder="Position">
                    </div>
                    <div class="field-col job-roles">
                        <label for="f-experiential-learning-${employmentIndex}-job-roles">Job Roles / Performed</label>
                        <textarea id="f-experiential-learning-${employmentIndex}-job-roles" name="pre_app_data[experiential_learning][${employmentIndex}][job_roles]" placeholder="Roles / Duties" rows="3"></textarea>
                    </div>
                </div>
            `;
            tbody.appendChild(card);
            employmentIndex++;
        }

        let trainingIndex = 1;
        function addTrainingRow() {
            const tbody = document.getElementById('training-tbody');
            const card = document.createElement('div');
            card.className = 'row-card training-card';
            card.id = `training-card-${trainingIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Training Entry #${trainingIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: var(--bad); border-color: var(--bad); color: white;">Remove</button>
                </div>
                <div class="row-card-body training-grid">
                    <div class="field-col course-name">
                        <label for="f-training-activities-${trainingIndex}-course-name">Course/Training Name</label>
                        <input id="f-training-activities-${trainingIndex}-course-name" type="text" name="pre_app_data[training_activities][${trainingIndex}][course_name]" placeholder="Course Title">
                    </div>
                    <div class="field-col location">
                        <label for="f-training-activities-${trainingIndex}-location">Location</label>
                        <input id="f-training-activities-${trainingIndex}-location" type="text" name="pre_app_data[training_activities][${trainingIndex}][location]" placeholder="Location">
                    </div>
                    <div class="field-col date-duration">
                        <label for="f-training-activities-${trainingIndex}-date-duration">Date & Duration</label>
                        <input id="f-training-activities-${trainingIndex}-date-duration" type="text" name="pre_app_data[training_activities][${trainingIndex}][date_duration]" placeholder="e.g. Nov 2024">
                    </div>
                    <div class="field-col activity-type">
                        <label for="f-training-activities-${trainingIndex}-activity-type">Activity Type</label>
                        <select id="f-training-activities-${trainingIndex}-activity-type" name="pre_app_data[training_activities][${trainingIndex}][activity_type]">
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

        let otherSkillIndex = 1;
        function addOtherSkillRow() {
            const tbody = document.getElementById('other-skills-tbody');
            const card = document.createElement('div');
            card.className = 'row-card other-skills-card';
            card.id = `other-skills-card-${otherSkillIndex}`;
            card.innerHTML = `
                <div class="row-card-header">
                    <span>Other Activity Entry #${otherSkillIndex + 1}</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="this.closest('.row-card').remove()" style="background: var(--bad); border-color: var(--bad); color: white;">Remove</button>
                </div>
                <div class="row-card-body other-skills-grid">
                    <div class="field-col other-activities">
                        <label for="f-other-learning-skills-${otherSkillIndex}-other-activities">Other Activity Description</label>
                        <input id="f-other-learning-skills-${otherSkillIndex}-other-activities" type="text" name="pre_app_data[other_learning_skills][${otherSkillIndex}][other_activities]" placeholder="Description">
                    </div>
                    <div class="field-col year">
                        <label for="f-other-learning-skills-${otherSkillIndex}-year">Year</label>
                        <input id="f-other-learning-skills-${otherSkillIndex}-year" type="text" name="pre_app_data[other_learning_skills][${otherSkillIndex}][year]" placeholder="Year">
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

        // Tab layout switcher function
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

            // Initialize default checklist for row 0
            document.getElementById('skills-grid-t0').innerHTML = getSkillsCheckboxesHTML('training_activities', 0);
            document.getElementById('skills-grid-o0').innerHTML = getSkillsCheckboxesHTML('other_learning_skills', 0);

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
                        li.innerHTML = `📄 <strong style="color: var(--ink);">${file.name}</strong> <span style="color: var(--ink-3); font-size: 11px;">(${sizeKB} KB)</span>`;
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
                        citizenshipIndicator.innerHTML = '<span style="color: var(--good);">🇲🇾 Malaysian Citizen verified (Valid IC Format)</span>';
                    } else if (value.length > 0) {
                        citizenshipIndicator.innerHTML = '<span style="color: var(--bad);">❌ Invalid format. APEL A candidates must be Malaysian Citizens with a valid 12-digit IC.</span>';
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
            const autosaveKey = 'apel_c_autosave_' + '{{ Auth::id() }}';
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
                    if (input.type === 'file' || input.type === 'password' || input.name === '_token' || input.name === 'submit_type') return;
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

            /**
             * The repeatable sections are built by JavaScript, so on a fresh page
             * load their rows do not exist yet and restored values would have
             * nowhere to go. Recreate the rows first, using the highest index
             * present in the saved payload.
             */
            function restoreDynamicRows(formData) {
                const sections = [
                    ['formal_learning',        typeof addEducationRow   === 'function' ? addEducationRow   : null],
                    ['experiential_learning',  typeof addEmploymentRow  === 'function' ? addEmploymentRow  : null],
                    ['training_activities',    typeof addTrainingRow    === 'function' ? addTrainingRow    : null],
                    ['other_learning_skills',  typeof addOtherSkillRow  === 'function' ? addOtherSkillRow  : null],
                ];

                sections.forEach(function (entry) {
                    const section = entry[0];
                    const addRow = entry[1];
                    if (!addRow) return;

                    // Plain string parsing rather than a built RegExp: the bracket
                    // and \d escapes do not survive being embedded in a Blade file.
                    const prefix = 'pre_app_data[' + section + '][';
                    let maxIndex = -1;
                    Object.keys(formData).forEach(function (key) {
                        if (key.indexOf(prefix) !== 0) return;
                        const rest = key.slice(prefix.length);
                        const end = rest.indexOf(']');
                        if (end === -1) return;
                        const idx = parseInt(rest.slice(0, end), 10);
                        if (!isNaN(idx)) maxIndex = Math.max(maxIndex, idx);
                    });
                    if (maxIndex < 1) return;

                    // Index 0 is rendered server-side; add rows until maxIndex exists.
                    for (let i = 0; i < maxIndex; i++) {
                        const existing = form.querySelector(
                            '[name^="pre_app_data[' + section + '][' + (i + 1) + ']"]'
                        );
                        if (!existing) addRow();
                    }
                });
            }

            function loadFormData() {
                const saved = localStorage.getItem(autosaveKey);
                if (!saved) return;
                try {
                    const formData = JSON.parse(saved);
                    restoreDynamicRows(formData);
                    const inputs = form.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (input.type === 'file' || input.type === 'password' || input.name === '_token' || input.name === 'submit_type') return;
                        
                        if (input.type === 'checkbox') {
                            const key = input.name + '_' + input.value;
                            if (formData[key] !== undefined) {
                                input.checked = formData[key];
                            }
                        } else if (input.type === 'radio') {
                            if (formData[input.name] !== undefined && formData[input.name] === input.value) {
                                input.checked = true;
                            }
                        } else {
                            if (formData[input.name] !== undefined) {
                                input.value = formData[input.name];
                            }
                        }
                    });
                    
                    // Trigger custom check handlers
                    if (reportTextarea) checkWordCount();
                } catch (e) {
                    console.error("Auto-save load failed:", e);
                }
            }

            form.addEventListener('input', saveFormData);
            form.addEventListener('change', saveFormData);
            
            // Load pre-existing auto-save data
            loadFormData();

            /*
             * If the server rejected the submission, bring the user to the problem
             * instead of leaving a summary at the top of the page pointing at a
             * field three screens down inside a collapsed tab.
             */
            (function revealFirstServerError() {
                const firstError = form.querySelector('.field-error');
                if (!firstError) return;

                const panel = firstError.closest('.tab-content');
                if (panel && panel.id) revealTab(panel.id);

                const control = firstError.previousElementSibling;
                if (control && typeof control.focus === 'function') {
                    control.classList.add('has-error');
                    try { control.focus({ preventScroll: true }); } catch (e) { /* not focusable */ }
                }
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            })();

            /*
             * Deliberately NOT clearing the backup here.
             *
             * This listener used to fire on the submit event - before the server
             * had accepted anything. When validation failed, the repeatable rows
             * re-rendered blank AND the localStorage copy that would have restored
             * them was already gone, losing 30+ minutes of work on the one form
             * where that hurts most.
             *
             * The key is now cleared only once the application genuinely exists:
             * on the applications index after a success redirect, and in the
             * draft-save AJAX success handler below.
             */

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
