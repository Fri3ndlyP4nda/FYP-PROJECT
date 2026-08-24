@extends('layouts.app')

@section('content')
    <div class="container app-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">APEL C Result</span>
                <h2>APEL C Application Details</h2>
                <p class="muted page-hero-text">
                    Review your APEL C assessment progress, grading outcome, and final credit approval decision.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('student.applications.print', $application->_id) }}" target="_blank" class="btn">🖨️ Print Portfolio</a>
                <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">Back to Applications</a>
            </div>
        </section>

        {{-- Application Progress Tracker --}}
        <section class="record-card" style="margin-bottom: 24px;">
            <div class="record-top">
                <div>
                    <p class="record-kicker">Application Progress</p>
                    <h3>APEL C Workflow Status</h3>
                </div>

                <div>
                    <span class="badge badge-pending">
                        Current Status: {{ $application->status ?? 'Pre-Application Submitted' }}
                    </span>
                </div>
            </div>

            @php
                $currentStatus = $application->status ?? 'Pre-Application Submitted';
                $isRejected = (($application->credit_decision ?? null) === 'rejected' || $currentStatus === 'Final Rejected');
                $assessmentStepName = ($application->assessment_type ?? '') === 'portfolio' ? 'Portfolio Procedure' : 'Assessment In Progress';

                if ($isRejected) {
                    $steps = [
                        'Pre-Application Submitted',
                        'Under Advisor Review',
                        'Advisor Approved',
                        'Payment Pending',
                        'Official Application Submitted',
                        'Evaluator Assigned',
                        $assessmentStepName,
                        'Final Rejected',
                    ];
                } else {
                    $steps = [
                        'Pre-Application Submitted',
                        'Under Advisor Review',
                        'Advisor Approved',
                        'Payment Pending',
                        'Official Application Submitted',
                        'Evaluator Assigned',
                        $assessmentStepName,
                        'Final Approved',
                    ];
                }

                if ($currentStatus === 'Advisor Rejected') {
                    $steps = ['Pre-Application Submitted', 'Under Advisor Review', 'Advisor Rejected'];
                    $currentIndex = 2;
                } elseif (($application->credit_status ?? null) === 'portfolio_failed') {
                    $steps = [
                        'Pre-Application Submitted',
                        'Under Advisor Review',
                        'Advisor Approved',
                        'Payment Pending',
                        'Official Application Submitted',
                        'Evaluator Assigned',
                        $assessmentStepName,
                        'Portfolio Failed',
                        'Appeal Available',
                    ];
                    $currentIndex = 7;
                } elseif (($application->credit_status ?? null) === 'appeal_submitted') {
                    $steps = [
                        'Pre-Application Submitted',
                        'Under Advisor Review',
                        'Advisor Approved',
                        'Payment Pending',
                        'Official Application Submitted',
                        'Evaluator Assigned',
                        $assessmentStepName,
                        'Portfolio Failed',
                        'Appeal Submitted',
                    ];
                    $currentIndex = 8;
                } else {
                    $currentIndex = match ($currentStatus) {
                        'Pre-Application Submitted' => 0,
                        'Under Advisor Review' => 1,
                        'Advisor Approved' => 2,
                        
                        'Payment Pending',
                        'Payment Submitted',
                        'Payment Verified',
                        'Payment Rejected' => 3,
                        
                        'Official Application Submitted' => 4,
                        'Assessor Assigned',
                        'Evaluator Assigned' => 5,
                        
                        'Assessment In Progress',
                        'Awaiting Final Decision',
                        'Awaiting Credit Decision',
                        'assessment_paper_uploaded' => 6,
                        
                        'Final Approved',
                        'Final Rejected' => 7,
                        
                        default => 0,
                    };
                }
            @endphp

            @php
                $percent = count($steps) > 1 ? ($currentIndex / (count($steps) - 1)) * 100 : 0;
            @endphp
            <div class="progress-timeline" style="margin-top: 20px;">
                <div class="progress-timeline-line-filled" style="width: calc({{ $percent }}% - {{ 140 * ($percent / 100) }}px);"></div>
                @foreach ($steps as $index => $step)
                    @php
                        $isCompleted = $index < $currentIndex;
                        $isActive = $index === $currentIndex;
                        $isFailed = ($isActive || $isCompleted) && (str_contains(strtolower($step), 'rejected') || str_contains(strtolower($step), 'failed'));
                        
                        $stepClass = '';
                        if ($isFailed) {
                            $stepClass = 'step-failed';
                        } elseif ($isActive) {
                            $stepClass = 'step-active';
                        } elseif ($isCompleted) {
                            $stepClass = 'step-completed';
                        }
                    @endphp
                    <div class="timeline-step {{ $stepClass }}">
                        <div class="step-icon">
                            @if ($isFailed)
                                ✕
                            @elseif ($isCompleted)
                                ✓
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <div class="step-label" style="font-size: 10px;">{{ $step }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="record-card">
            <div class="record-top">
                <div>
                    <p class="record-kicker">{{ $application->application_type }}</p>
                    <h3>{{ $application->program_applied }}</h3>
                </div>

                <div>
                    @if (($application->credit_decision ?? 'pending') === 'approved')
                        <span class="badge badge-approved">Credit Approved</span>
                    @elseif (($application->credit_decision ?? 'pending') === 'rejected')
                        <span class="badge badge-rejected">Credit Rejected</span>
                    @else
                        <span class="badge badge-pending">Credit Decision Pending</span>
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
                    <span class="meta-label">Credit Status</span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $application->credit_status ?? 'awaiting_assessment')) }}</strong>
                </div>

                <div class="meta-box">
                    <span class="meta-label">Payment Status</span>
                    <strong>{{ ucfirst($application->payment_status ?? 'pending') }}</strong>
                </div>

                <div class="meta-box">
                    <span class="meta-label">Assigned Evaluator</span>
                    <strong>
                        @if ($application->evaluator_id)
                            {{ \App\Models\User::where('_id', $application->evaluator_id)->value('name') ?? 'Unknown' }}
                        @else
                            Not Assigned
                        @endif
                    </strong>
                </div>
            </div>

            <div class="record-body-grid">
                <div class="record-panel">
                    <h4>Credit Decision</h4>
                    <p class="feedback-text">
                        {{ ucfirst(str_replace('_', ' ', $application->credit_decision ?? 'pending')) }}
                    </p>
                </div>

                <div class="record-panel">
                    <h4>Approved Credit Hours</h4>
                    <p class="feedback-text">
                        {{ $application->credit_hours_approved ?? 'Not decided yet' }}
                    </p>
                </div>
            </div>

            <div class="record-body-grid">
                <div class="record-panel">
                    <h4>Course Code</h4>
                    <p class="feedback-text">
                        {{ $application->credit_course_code ?? 'Not decided yet' }}
                    </p>
                </div>

                <div class="record-panel">
                    <h4>Course Name</h4>
                    <p class="feedback-text">
                        {{ $application->credit_course_name ?? 'Not decided yet' }}
                    </p>
                </div>
            </div>

            <div class="record-panel">
                <h4>Credit Remarks</h4>
                <p class="feedback-text">
                    {{ $application->credit_remarks ?? 'No credit remarks available yet.' }}
                </p>
            </div>

            <div class="record-panel" style="margin-top: 20px;">
                <h4>Evaluator Feedback</h4>
                <p class="feedback-text" style="white-space: pre-wrap;">{{ $application->evaluator_feedback ?? 'No feedback has been provided yet.' }}</p>
            </div>

            <div class="record-panel" style="margin-top: 20px;">
                <h4>Uploaded Evidence & Portfolio</h4>

                <p class="feedback-text">
                    <strong>Evidence File(s):</strong>
                </p>

                @if (!empty($application->evidence_file))
                    <ul>
                        @foreach ($application->evidence_file as $file)
                            @php
                                $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                            @endphp
                            <li>
                                <a href="{{ asset('storage/' . $filePath) }}" target="_blank">
                                    {{ $fileName }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="feedback-text">
                        No evidence file uploaded.
                    </p>
                @endif

                <p class="feedback-text">
                    <strong>Portfolio File(s):</strong>
                </p>

                @if (!empty($application->portfolio_file))
                    <ul>
                        @foreach ($application->portfolio_file as $file)
                            @php
                                $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                            @endphp
                            <li>
                                <a href="{{ asset('storage/' . $filePath) }}" target="_blank">
                                    {{ $fileName }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="feedback-text">
                        No portfolio file uploaded.
                    </p>
                @endif
            </div>

            @php
                $assessmentPaper = \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)
                    ->where('status', 'active')
                    ->first();
            @endphp

            @if (($application->assessment_type ?? '') === 'portfolio')
                @php
                    $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();
                @endphp

                @if ($submission && $submission->graded_at)
                    <div class="record-panel" style="margin-top: 20px; border-top: 4px solid #10b981; background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="color: #065f46; margin-bottom: 10px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                            <span>✓</span> Portfolio Evaluation Completed
                        </h3>
                        <div style="background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 8px; padding: 14px; margin-bottom: 15px; font-size: 13.5px; color: #065f46; line-height: 1.5;">
                            Your portfolio evaluation is completed. The final grading details are as follows:
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; font-size: 14px;">
                            <div>Overall Score: <strong style="font-size: 15px; color: #1f2937;">{{ $submission->score }}%</strong></div>
                            <div>Outcome: <strong style="font-size: 15px; color: {{ $submission->result === 'pass' ? '#10b981' : '#ef4444' }}; text-transform: uppercase;">{{ $submission->result === 'pass' ? 'PASS' : 'FAIL' }}</strong></div>
                        </div>

                        <!-- CLO Scores if available -->
                        @if(isset($submission->evaluator_1_clo1) || isset($submission->evaluator_2_clo1))
                            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-top: 15px;">
                                <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 13px; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Evaluator Rubric Scores</h4>
                                
                                @if(isset($submission->evaluator_1_clo1))
                                    <div style="margin-bottom: 10px;">
                                        <strong style="font-size: 12px; color: #8B1E3F; display: block; margin-bottom: 4px;">Evaluator 1 Scores:</strong>
                                        <span style="font-size: 12.5px; color: #374151;">
                                            CLO1: <strong>{{ $submission->evaluator_1_clo1 }}/10</strong> | 
                                            CLO2: <strong>{{ $submission->evaluator_1_clo2 }}/10</strong> | 
                                            CLO3: <strong>{{ $submission->evaluator_1_clo3 }}/10</strong> | 
                                            CLO4: <strong>{{ $submission->evaluator_1_clo4 }}/10</strong>
                                        </span>
                                    </div>
                                @endif

                                @if(isset($submission->evaluator_2_clo1))
                                    <div>
                                        <strong style="font-size: 12px; color: #8B1E3F; display: block; margin-bottom: 4px;">Evaluator 2 Scores:</strong>
                                        <span style="font-size: 12.5px; color: #374151;">
                                            CLO1: <strong>{{ $submission->evaluator_2_clo1 }}/10</strong> | 
                                            CLO2: <strong>{{ $submission->evaluator_2_clo2 }}/10</strong> | 
                                            CLO3: <strong>{{ $submission->evaluator_2_clo3 }}/10</strong> | 
                                            CLO4: <strong>{{ $submission->evaluator_2_clo4 }}/10</strong>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="record-panel" style="margin-top: 20px; border-top: 4px solid #8B1E3F; background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="color: #8B1E3F; margin-bottom: 10px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                            <span>📄</span> Portfolio Assessment In Progress
                        </h3>
                        <p style="font-size: 13px; color: #4b5563; line-height: 1.5; margin-bottom: 0;">
                            Your advisor has recommended the <strong>Portfolio Mode of Assessment</strong>. The evaluator is currently reviewing your uploaded portfolio. No further action is required from you.
                        </p>
                    </div>
                @endif
            @else
                <div class="record-panel" style="margin-top: 20px;">
                    <h4>Assessment Paper</h4>

                    @if ($assessmentPaper)
                        <p class="feedback-text">
                            The evaluator has uploaded an assessment paper for this APEL C application.
                        </p>

                        <p class="feedback-text">
                            <strong>Assessment Title:</strong>
                            {{ $assessmentPaper->title }}
                        </p>

                        <a href="{{ route('student.assessment.show', $application->_id) }}" class="btn">
                            View Assessment Paper
                        </a>
                    @else
                        <p class="feedback-text">
                            No assessment paper has been uploaded yet. Please wait for the evaluator.
                        </p>
                    @endif
                </div>
            @endif

            <div class="record-panel" style="margin-top: 20px;">
                <h4>Payment Submission</h4>

                <p class="feedback-text">
                    <strong>Payment Type:</strong>
                    {{ $application->payment_type ?? 'APEL C Processing / Credit Transfer Fee' }}
                </p>

                <p class="feedback-text">
                    <strong>Payment Status:</strong>
                    {{ ucfirst($application->payment_status ?? 'pending') }}
                </p>

                @if ($application->payment_receipt)
                    <p class="feedback-text">
                        <strong>Uploaded Receipt:</strong>
                        <a href="{{ asset('storage/' . $application->payment_receipt) }}" target="_blank" class="link">
                            View Payment Receipt
                        </a>
                    </p>
                @endif

                @if (in_array($application->payment_status ?? 'pending', ['submitted', 'verified']) && $application->payment_remarks)
                    <p class="feedback-text">
                        <strong>Payment Remarks:</strong>
                        {{ $application->payment_remarks }}
                    </p>
                @endif

                @if (($application->payment_status ?? 'pending') === 'verified')
                    <p class="feedback-text" style="color: #059669; font-weight: 500; margin-top: 15px;">
                        Your payment has been verified by the Faculty Academic Office.
                    </p>
                @elseif (($application->payment_status ?? 'pending') === 'submitted')
                    <p class="feedback-text" style="color: #0d9488; font-weight: 500; margin-top: 15px;">
                        You have submitted your payment receipt. Please wait for verification by the Faculty Academic Office.
                    </p>
                @else
                    <form method="POST" action="{{ route('student.applications.payment', $application->_id) }}"
                        enctype="multipart/form-data" style="margin-top: 15px;">
                        @csrf

                        <label>Upload Payment Receipt</label>
                        <input type="file" name="payment_receipt" accept=".pdf,.jpg,.jpeg,.png" required>

                        <small style="display:block; margin-top:5px; color:#666;">
                            Allowed format: PDF, JPG, JPEG, PNG. Maximum size: 5MB.
                        </small>

                        <label>Payment Remarks</label>
                        <textarea name="payment_remarks" rows="4" placeholder="Example: Payment completed through PayHub.">{{ old('payment_remarks', $application->payment_remarks) }}</textarea>

                        <div class="form-submit-row">
                            <button type="submit" class="btn">
                                Submit Payment Receipt
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- APPEAL WORKFLOW --}}
            @if (($application->status ?? '') === 'Final Rejected' || ($application->credit_decision ?? '') === 'rejected')
                <div class="record-panel" style="margin-top: 20px; border-top: 2px solid #ef4444; padding-top: 20px;">
                    @if (($application->appeal_status ?? null) !== 'submitted')
                        <h4 style="color: #b91c1c; margin-bottom: 10px;">Submit Appeal Request</h4>
                        <p class="feedback-text" style="margin-bottom: 15px;">
                            Your APEL C credit application has been rejected. In accordance with UTM APEL C regulations, you may submit an appeal request detailing your learning outcome justifications.
                        </p>

                        <form method="POST" action="{{ route('student.applications.appeal', $application->_id) }}">
                            @csrf

                            <label style="font-weight:700; color:#b91c1c;">Reason for Appeal</label>
                            <textarea name="appeal_remarks" rows="5" class="form-control" placeholder="Explain how your prior learning justifies the credit award..." required></textarea>

                            <div class="form-submit-row">
                                <button type="submit" class="btn" style="background: #b91c1c;">
                                    Submit Appeal Form
                                </button>
                            </div>
                        </form>
                    @else
                        <h4 style="color: #15803d; margin-bottom: 10px;">Appeal Request Submitted</h4>
                        <p class="feedback-text" style="margin-bottom: 15px;">
                            Your appeal has been successfully received by the Faculty Academic Office.
                        </p>

                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                            <p class="feedback-text" style="margin-bottom: 8px;">
                                <strong>Date of Submission:</strong> {{ $application->appeal_submitted_at ? $application->appeal_submitted_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }}
                            </p>
                            <p class="feedback-text" style="margin-bottom: 0;">
                                <strong>Your Statement:</strong><br>
                                <em>{{ $application->appeal_remarks }}</em>
                            </p>
                        </div>

                        <p class="feedback-text" style="font-weight:700; color:#15803d;">
                            Status: Re-evaluation Under Review. The Academic Office is checking your appeal.
                        </p>
                    @endif
                </div>
            @endif
        </section>

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
        </style>

        <!-- Submitted Pre-Application Details Card -->
        <section class="record-card" style="margin-top: 24px;">
            <div class="record-top">
                <div>
                    <p class="record-kicker">APEL C Pre-Application Details</p>
                    <h3>Submitted Form Data</h3>
                </div>
            </div>

            <div class="form-tabs" style="margin-top: 20px; display: flex; gap: 6px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                <button type="button" class="tab-link active" onclick="openDetailsTab(event, 'details-particulars')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Personal Particulars</button>
                <button type="button" class="tab-link" onclick="openDetailsTab(event, 'details-education')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Formal Learning</button>
                <button type="button" class="tab-link" onclick="openDetailsTab(event, 'details-experience')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Experience & Training</button>
                <button type="button" class="tab-link" onclick="openDetailsTab(event, 'details-other-skills')" style="border: none; background: transparent; padding: 6px 12px; font-weight: 600; cursor: pointer; color: #6b7280; font-size: 13px;">Other Learning</button>
            </div>

            <!-- TAB 1: Personal Particulars -->
            <div id="details-particulars" class="details-tab-content" style="display: block; margin-top: 20px;">
                @php
                    $personal = $application->pre_app_data['personal_particulars'] ?? [];
                @endphp
                <div class="record-meta-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div class="meta-box">
                        <span class="meta-label">Full Name</span>
                        <strong>{{ $personal['name'] ?? $student->name }}</strong>
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
            <div id="details-education" class="details-tab-content" style="display: none; margin-top: 20px;">
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
                    <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13.5px;">No formal learning recorded.</p>
                @endif
            </div>

            <!-- TAB 3: Experience & Training -->
            <div id="details-experience" class="details-tab-content" style="display: none; margin-top: 20px;">
                @php
                    $jobs = $application->pre_app_data['experiential_learning'] ?? [];
                    $trainings = $application->pre_app_data['training_activities'] ?? [];
                    $skillsList = [
                        "Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills",
                        "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility",
                        "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"
                    ];
                @endphp
                
                <h4 style="color: #8B1E3F; margin-bottom: 12px; font-size: 15px; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">Experiential Learning (Employment History)</h4>
                @if(!empty($jobs) && count($jobs) > 0 && (!empty($jobs[0]['employer_name']) || !empty($jobs[0]['position_held'])))
                    <div class="cards-container" style="margin-bottom: 30px;">
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
                                            <p style="margin: 4px 0 0 0; color: #374151; font-size: 13px; line-height: 1.5; white-space: pre-wrap;">{{ $item['job_roles'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13.5px; margin-bottom: 30px;">No employment history recorded.</p>
                @endif

                <h4 style="color: #8B1E3F; margin-bottom: 12px; font-size: 15px; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">Training Activities</h4>
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
                                                    <div style="font-size: 12.5px; color: {{ in_array($sIdx + 1, $checkedSkills) ? '#111827' : '#9ca3af' }}; display: flex; align-items: center; gap: 6px;">
                                                        <span style="font-size: 14px;">{{ in_array($sIdx + 1, $checkedSkills) ? '☑' : '☐' }}</span>
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
                    <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13.5px;">No training activities recorded.</p>
                @endif
            </div>

            <!-- TAB 4: Other Learning -->
            <div id="details-other-skills" class="details-tab-content" style="display: none; margin-top: 20px;">
                @php
                    $otherSkills = $application->pre_app_data['other_learning_skills'] ?? [];
                    $langSkills = $application->pre_app_data['language_skills'] ?? [];
                    $skillsList = [
                        "Knowledge & Understanding", "Cognitive skills", "Practical Skills", "Interpersonal Skills",
                        "Communication skills", "Digital skills", "Numeracy skills", "Leadership, Autonomy & Responsibility",
                        "Personal Skills", "Entrepreneurial skills", "Ethics and Professionalism skills"
                    ];
                @endphp

                <h4 style="color: #8B1E3F; margin-bottom: 12px; font-size: 15px; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">Other Learning Skills / Activities</h4>
                @if(!empty($otherSkills) && count($otherSkills) > 0 && (!empty($otherSkills[0]['other_activities']) || !empty($otherSkills[0]['year'])))
                    <div class="cards-container" style="margin-bottom: 30px;">
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
                                                    <div style="font-size: 12.5px; color: {{ in_array($sIdx + 1, $checkedSkills) ? '#111827' : '#9ca3af' }}; display: flex; align-items: center; gap: 6px;">
                                                        <span style="font-size: 14px;">{{ in_array($sIdx + 1, $checkedSkills) ? '☑' : '☐' }}</span>
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
                    <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13.5px; margin-bottom: 30px;">No other learning activities recorded.</p>
                @endif

                <h4 style="color: #8B1E3F; margin-bottom: 12px; font-size: 15px; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">Language Skills</h4>
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
                                    <td style="padding: 10px; font-size: 13.5px; font-weight: 600; color: #1f2937;">{{ $item['language'] ?? 'N/A' }}</td>
                                    <td style="padding: 10px; text-align: center; font-size: 13.5px; color: #374151;">{{ $item['listening'] ?? '3' }} / 4</td>
                                    <td style="padding: 10px; text-align: center; font-size: 13.5px; color: #374151;">{{ $item['reading'] ?? '3' }} / 4</td>
                                    <td style="padding: 10px; text-align: center; font-size: 13.5px; color: #374151;">{{ $item['speaking'] ?? '3' }} / 4</td>
                                    <td style="padding: 10px; text-align: center; font-size: 13.5px; color: #374151;">{{ $item['writing'] ?? '3' }} / 4</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 20px;">
                        Scale Competency - 1: Poor; 2: Average; 3: Good; 4: Excellent
                    </div>
                @else
                    <p class="muted" style="font-style: italic; color: #6b7280; font-size: 13.5px;">No language skills recorded.</p>
                @endif
            </div>
        </section>
    </div>

    <script>
        function openPortfolioTab(evt, tabId) {
            const contents = document.getElementsByClassName("portfolio-tab-content");
            for (let i = 0; i < contents.length; i++) {
                contents[i].style.display = "none";
            }
            
            const links = document.querySelectorAll(".form-tabs .tab-link");
            links.forEach(link => {
                link.classList.remove("active");
                link.style.color = "#6b7280";
                link.style.background = "transparent";
            });
            
            document.getElementById(tabId).style.display = "block";
            evt.currentTarget.classList.add("active");
            evt.currentTarget.style.color = "#ffffff";
            evt.currentTarget.style.background = "#8B1E3F";
            evt.currentTarget.style.borderRadius = "6px";
        }

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
            evt.currentTarget.style.color = "#ffffff";
            evt.currentTarget.style.background = "#8B1E3F";
            evt.currentTarget.style.borderRadius = "6px";
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Initialize default style for active details tab link
            const activeLink = document.querySelector(".form-tabs .tab-link.active");
            if (activeLink) {
                activeLink.style.color = "#ffffff";
                activeLink.style.background = "#8B1E3F";
                activeLink.style.borderRadius = "6px";
            }

            const essayTextareas = document.querySelectorAll(".essay-input");
            essayTextareas.forEach(textarea => {
                const countDiv = textarea.nextElementSibling;
                textarea.addEventListener("input", function() {
                    const words = textarea.value.trim().split(/\s+/).filter(w => w.length > 0).length;
                    if (words < 200) {
                        countDiv.innerHTML = `<span style="color: #ef4444; font-weight:600;">Word count: ${words} words (Minimum 200 words required)</span>`;
                    } else {
                        countDiv.innerHTML = `<span style="color: #10b981; font-weight:600;">Word count: ${words} words (Valid)</span>`;
                    }
                });
            });
        });
    </script>
@endsection
