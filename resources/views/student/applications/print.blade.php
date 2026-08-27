<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM APEL-{{ $application->application_type === 'APEL A' ? 'A' : 'C' }} Report - {{ $student->name }}</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            line-height: 1.4;
            color: #000000;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        
        .print-toolbar {
            background: #ffffff;
            border-bottom: 2px solid #6e1730;
            padding: 15px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(139, 30, 63, 0.1);
        }
        
        .print-toolbar-logo {
            font-size: 16px;
            font-weight: bold;
            color: #6e1730;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cfc9be;
            background: #ffffff;
            color: #4e4b45;
        }

        .btn-primary {
            background: #6e1730;
            border-color: #6e1730;
            color: #ffffff;
        }

        .print-preview-shell {
            background-color: #f1efea;
            min-height: 100vh;
            padding: 30px 10px;
        }

        .print-paper-sheet {
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 20mm 20mm;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            box-sizing: border-box;
            position: relative;
        }

        .print-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .print-header h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .print-header p {
            font-size: 12px;
            margin: 3px 0 0 0;
            font-style: italic;
        }

        h3.section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000000;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 12px;
        }

        table.form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.form-table th, table.form-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
            text-align: left;
            font-size: 12px;
            vertical-align: top;
        }

        table.form-table th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .meta-label {
            font-weight: bold;
            width: 180px;
        }

        .page-break {
            page-break-before: always;
        }

        .print-footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            border-top: 1px solid #cccccc;
            padding-top: 6px;
            font-size: 10px;
            color: #555555;
            display: flex;
            justify-content: space-between;
        }

        .essay-box {
            border: 1px solid #000000;
            padding: 12px;
            background: #fafafa;
            font-size: 12.5px;
            line-height: 1.5;
            margin-bottom: 15px;
            white-space: pre-wrap;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            border-top: 1px solid #000000;
            padding-top: 8px;
            text-align: center;
            font-size: 12px;
        }

        @media print {
            .print-toolbar, .no-print {
                display: none !important;
            }
            .print-preview-shell {
                background: transparent !important;
                padding: 0 !important;
            }
            .print-paper-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: 100% !important;
            }
            .print-footer {
                position: fixed;
                bottom: 10mm;
                left: 10mm;
                right: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <div class="print-toolbar-logo">
            <span>🎓</span> UTM APEL {{ $application->application_type === 'APEL A' ? 'A' : 'C' }} Report Export
        </div>
        <div style="display: flex; gap: 8px;">
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.applications.assign.form', $application->_id) }}" class="btn">
                    ← Back to Management
                </a>
            @elseif(auth()->user()->role === 'evaluator')
                <a href="{{ route('evaluator.applications.show', $application->_id) }}" class="btn">
                    ← Back to Review
                </a>
            @else
                <a href="{{ route('student.applications.index') }}" class="btn">
                    ← Back to Applications
                </a>
            @endif
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Open Print Dialog
            </button>
        </div>
    </div>

    <div class="print-preview-shell">
        @if ($application->application_type === 'APEL A')
            {{-- PAGE 1: APEL A APPLICATION FORM --}}
            <div class="print-paper-sheet">
                <div class="print-header">
                    <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                    <p>APEL (A) ADMISSION APPLICATION FORM (PORTFOLIO REPORT)</p>
                </div>

                <h3 class="section-title">PART A: PERSONAL PARTICULARS</h3>
                <table class="form-table">
                    <tr>
                        <td class="meta-label">Full Name</td>
                        <td>{{ $student->name }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Email Address</td>
                        <td>{{ $student->email }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Age</td>
                        <td>{{ $application->age ?? 'N/A' }} years old</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Programme Applied</td>
                        <td>{{ $application->program_applied }}</td>
                    </tr>
                </table>

                <h3 class="section-title">PART B: ACADEMIC & EMPLOYMENT BACKGROUND</h3>
                <table class="form-table">
                    <tr>
                        <td class="meta-label">Highest Academic Qualification</td>
                        <td>{{ $application->highest_qualification ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Institution / University Name</td>
                        <td>{{ $application->university_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Current Job / Position</td>
                        <td>{{ $application->current_job ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Current Company / Employer</td>
                        <td>{{ $application->company_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Years of Working Experience</td>
                        <td>{{ $application->working_experience_years ?? '0' }} Year(s)</td>
                    </tr>
                </table>

                <h3 class="section-title">PART C: RELEVANT EXPERIENCE & MOTIVATION</h3>
                <h4 style="font-size:12.5px; font-weight:bold; margin-top:15px; margin-bottom:5px;">Statement of Working Experience</h4>
                <div class="essay-box" style="min-height: 120px;">{{ $application->working_experience_details ?? 'No details provided.' }}</div>

                <h4 style="font-size:12.5px; font-weight:bold; margin-top:15px; margin-bottom:5px;">Reason for Applying through APEL A</h4>
                <div class="essay-box" style="min-height: 120px;">{{ $application->reason_applying ?? 'No details provided.' }}</div>

                <div class="print-footer">
                    <span>UTM Faculty of Computing APEL(A) Office</span>
                    <span>Page 1 of 2</span>
                </div>
            </div>

            {{-- PAGE 2: EVALUATION & DECISION REPORT --}}
            <div class="print-paper-sheet page-break">
                <div class="print-header">
                    <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                    <p>APEL (A) EVALUATION & DECISION REPORT</p>
                </div>

                <h3 class="section-title">PART A: EVALUATOR RECOMMENDATIONS</h3>
                @if($application->evaluator_id)
                    <table class="form-table">
                        <tr>
                            <td class="meta-label">First Evaluator</td>
                            <td><strong>{{ $evaluator->name ?? 'Assigned Evaluator 1' }}</strong></td>
                        </tr>
                        <tr>
                            <td class="meta-label">Recommendation</td>
                            <td>
                                @if($application->evaluator_1_reviewed_at)
                                    <strong>{{ ucfirst(str_replace('_', ' ', $application->evaluator_1_decision)) }}</strong>
                                    (Reviewed on {{ $application->evaluator_1_reviewed_at }})
                                @else
                                    <span style="font-style: italic; color: #555555;">Review pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="meta-label">Feedback / Remarks</td>
                            <td>{{ $application->evaluator_1_feedback ?? 'No feedback submitted.' }}</td>
                        </tr>
                    </table>
                @else
                    <div style="border: 1px dashed #000000; padding: 10px; text-align: center; font-style: italic; margin-bottom: 15px;">
                        No first evaluator has been assigned yet.
                    </div>
                @endif

                @if($application->evaluator_2_id)
                    <table class="form-table" style="margin-top: 15px;">
                        <tr>
                            <td class="meta-label">Second Evaluator</td>
                            <td><strong>{{ $evaluator2->name ?? 'Assigned Evaluator 2' }}</strong></td>
                        </tr>
                        <tr>
                            <td class="meta-label">Recommendation</td>
                            <td>
                                @if($application->evaluator_2_reviewed_at)
                                    <strong>{{ ucfirst(str_replace('_', ' ', $application->evaluator_2_decision)) }}</strong>
                                    (Reviewed on {{ $application->evaluator_2_reviewed_at }})
                                @else
                                    <span style="font-style: italic; color: #555555;">Review pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="meta-label">Feedback / Remarks</td>
                            <td>{{ $application->evaluator_2_feedback ?? 'No feedback submitted.' }}</td>
                        </tr>
                    </table>
                @endif

                <h3 class="section-title">PART B: FINAL ADMISSION DECISION</h3>
                <table class="form-table">
                    <tr>
                        <td class="meta-label">Consolidated Outcome</td>
                        <td>
                            @if(in_array($application->status ?? '', ['Final Approved', 'Final Rejected']))
                                <strong style="text-transform: uppercase;">{{ $application->status }}</strong>
                            @else
                                <span style="font-style: italic;">Awaiting Final Decision</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Final Decision Date</td>
                        <td>{{ $application->finalized_at ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Remarks / Conditions</td>
                        <td>{{ $application->final_decision_remarks ?? 'No remarks provided.' }}</td>
                    </tr>
                </table>

                <div class="signature-grid" style="margin-top: 80px;">
                    <div class="signature-box" style="border: none;"></div>
                    <div class="signature-box">
                        <strong>Academic Office Registrar</strong><br>
                        Universiti Teknologi Malaysia
                    </div>
                </div>

                <div class="print-footer">
                    <span>UTM Faculty of Computing APEL(A) Office</span>
                    <span>Page 2 of 2</span>
                </div>
            </div>
        @else
            {{-- PAGE 1: PRE-APPLICATION FORM --}}
        <div class="print-paper-sheet">
            <div class="print-header">
                <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                <p>APEL (C) PRE-APPLICATION FORM (LAMPIRAN D)</p>
            </div>

            <h3 class="section-title">PART A: PERSONAL PARTICULARS</h3>
            <table class="form-table">
                @php
                    $personal = $application->pre_app_data['personal_particulars'] ?? [];
                @endphp
                <tr>
                    <td class="meta-label">Full Name</td>
                    <td>{{ $personal['name'] ?? $student->name }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Matric No.</td>
                    <td>{{ $personal['matric_no'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Identity Card No.</td>
                    <td>{{ $personal['ic_no'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Programme of Study</td>
                    <td>{{ $application->program_applied }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Highest Academic Qualification</td>
                    <td>{{ $personal['highest_qualification'] ?? 'Diploma' }}</td>
                </tr>
            </table>

            <h3 class="section-title">PART B (i): FORMAL LEARNING (CERTIFICATED EDUCATION)</h3>
            <table class="form-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Year</th>
                        <th>Title of Certification</th>
                        <th>Level of Award</th>
                        <th>Awarding Body</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $formal = $application->pre_app_data['formal_learning'] ?? [];
                    @endphp
                    @forelse($formal as $item)
                        <tr>
                            <td>{{ $item['year_awarded'] ?? '' }}</td>
                            <td>{{ $item['title_of_certification'] ?? '' }}</td>
                            <td>{{ $item['level_of_award'] ?? '' }}</td>
                            <td>{{ $item['awarding_body'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; font-style: italic;">No formal learning recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="section-title">PART B (ii): EXPERIENTIAL LEARNING (EMPLOYMENT HISTORY)</h3>
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Employer Name</th>
                        <th style="width: 120px;">Duration</th>
                        <th style="width: 140px;">Position</th>
                        <th>Job Roles & Responsibilities</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $jobs = $application->pre_app_data['experiential_learning'] ?? [];
                    @endphp
                    @forelse($jobs as $item)
                        <tr>
                            <td>{{ $item['employer_name'] ?? '' }}<br><small>{{ $item['contact_address'] ?? '' }}</small></td>
                            <td>{{ $item['time_from'] ?? '' }} - {{ $item['time_to'] ?? '' }}</td>
                            <td>{{ $item['position_held'] ?? '' }}</td>
                            <td>{{ $item['job_roles'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; font-style: italic;">No employment history recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="print-footer">
                <span>UTM Faculty of Computing APEL(C) Office</span>
                <span>Page 1</span>
            </div>
        </div>

        {{-- PAGE 2: TRAINING, LANGUAGE, REFEREES & DECLARATION --}}
        <div class="print-paper-sheet page-break">
            <div class="print-header">
                <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                <p>APEL (C) PRE-APPLICATION DETAILS CONTINUED</p>
            </div>

            <h3 class="section-title">PART B (ii) CONTINUED: TRAINING ACTIVITIES</h3>
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Course / Training Title</th>
                        <th>Location</th>
                        <th>Date & Duration</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $trainings = $application->pre_app_data['training_activities'] ?? [];
                    @endphp
                    @forelse($trainings as $item)
                        <tr>
                            <td>{{ $item['course_name'] ?? '' }}</td>
                            <td>{{ $item['location'] ?? '' }}</td>
                            <td>{{ $item['date_duration'] ?? '' }}</td>
                            <td>{{ $item['activity_type'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; font-style: italic;">No training activities recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="section-title">PART B (iv): LANGUAGE SKILLS COMPETENCY</h3>
            <table class="form-table" style="text-align: center;">
                <thead>
                    <tr>
                        <th style="text-align: left;">Language</th>
                        <th>Listening (1-4)</th>
                        <th>Reading (1-4)</th>
                        <th>Speaking (1-4)</th>
                        <th>Writing (1-4)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $languages = $application->pre_app_data['language_skills'] ?? [];
                    @endphp
                    @forelse($languages as $item)
                        <tr>
                            <td style="text-align: left; font-weight: bold;">{{ $item['language'] ?? '' }}</td>
                            <td>{{ $item['listening'] ?? 'N/A' }}</td>
                            <td>{{ $item['reading'] ?? 'N/A' }}</td>
                            <td>{{ $item['speaking'] ?? 'N/A' }}</td>
                            <td>{{ $item['writing'] ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; font-style: italic;">No language competency scores recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="section-title">PART C (ii): REFEREES</h3>
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Name & Position</th>
                        <th>Organisation</th>
                        <th>Contact Details</th>
                        <th>Relationship</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $referees = $application->pre_app_data['referees'] ?? [];
                    @endphp
                    @forelse($referees as $item)
                        <tr>
                            <td><strong>{{ $item['referee_name'] ?? '' }}</strong><br>{{ $item['referee_position'] ?? '' }}</td>
                            <td>{{ $item['referee_organisation'] ?? '' }}</td>
                            <td>Tel: {{ $item['referee_phone_mobile'] ?? '' }}<br>Email: {{ $item['referee_email'] ?? '' }}</td>
                            <td>{{ $item['referee_relationship'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; font-style: italic;">No referees provided.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="section-title">PART D: STUDENT SELF-DECLARATION</h3>
            <div style="border: 1px solid #000000; padding: 10px; margin-bottom: 20px; font-size: 11.5px; line-height: 1.4;">
                I hereby declare that all of the information/documents provided to support this application are authentic, true and accurate. I fully understand that the Universiti Teknologi Malaysia reserves the right to reject my application if proven otherwise.
            </div>

            <table style="width: 100%; border: none; font-size: 13px;">
                <tr style="background: transparent;">
                    <td style="border: none;"><strong>Signature Name:</strong> {{ $application->pre_app_data['self_declaration']['name_as_per_ic'] ?? $student->name }}</td>
                    <td style="border: none; text-align: right;"><strong>Date Declared:</strong> {{ $application->pre_app_data['self_declaration']['date_declared'] ?? $application->submission_date }}</td>
                </tr>
            </table>

            <div class="print-footer">
                <span>UTM Faculty of Computing APEL(C) Office</span>
                <span>Page 2</span>
            </div>
        </div>

        {{-- PAGE 3: SELF-ASSESSMENT & ADVISOR RECOMMENDATION --}}
        <div class="print-paper-sheet page-break">
            <div class="print-header">
                <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                <p>APEL (C) SELF-ASSESSMENT & ADVISOR CONFIRMATION FORM</p>
            </div>

            <h3 class="section-title">PART A: SELF-ASSESSMENT CLO SUMMARY</h3>
            <table class="form-table">
                <thead>
                    <tr>
                        <th style="width: 250px;">Course Learning Outcome (CLO)</th>
                        <th>Student Learning Description (Prior Studies / Work Career)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>CLO1:</strong> Analyse IT security governance, risk management frameworks, policies, and standards.</td>
                        <td>{{ $application->self_assessment['clo_descriptions']['clo1'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>CLO2:</strong> Evaluate applications of security and management, providing justifications.</td>
                        <td>{{ $application->self_assessment['clo_descriptions']['clo2'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>CLO3:</strong> Complete risk identification, analysis, assessment, and control cycles.</td>
                        <td>{{ $application->self_assessment['clo_descriptions']['clo3'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>CLO4:</strong> Construct organisation-wide security plans/policies, and measure safeguards.</td>
                        <td>{{ $application->self_assessment['clo_descriptions']['clo4'] ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>

            <h3 class="section-title">PART B: ADVISOR CONFIRMATION & RECOMMENDED MODE OF ASSESSMENT</h3>
            @php
                $isApprovedOrGraded = in_array($application->status ?? 'Pre-Application Submitted', ['Assessment In Progress', 'Awaiting Final Decision', 'Final Approved', 'Credit Approved', 'Approved']);
                $showAdvisorConfirmation = !empty($application->advisor_name) || $isApprovedOrGraded;
            @endphp

            @if($showAdvisorConfirmation)
                @php
                    $advisorName = $application->advisor_name ?? 'Arif Termizi';
                    $advisorDate = $application->advisor_approved_at ?? ($application->submission_date ?? now()->format('Y-m-d'));
                    $recommendation = $application->advisor_evaluation['recommendation'] ?? 'Recommended';
                    $modeOfAssessment = $application->mode_of_assessment ?? 'portfolio';
                    $remarks = $application->advisor_evaluation['remarks'] ?? 'The candidate has been recommended for portfolio assessment based on pre-application review.';
                    $scores = $application->advisor_evaluation['clo_scores'] ?? ['clo1' => 3, 'clo2' => 3, 'clo3' => 3, 'clo4' => 3];
                @endphp
                <table class="form-table">
                    <tr>
                        <td class="meta-label">Advisor Name</td>
                        <td>{{ $advisorName }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Evaluation Date</td>
                        <td>{{ $advisorDate }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Assessment Recommendation</td>
                        <td><strong>{{ $recommendation }}</strong></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Assessment Mode</td>
                        <td><strong>{{ ucfirst($modeOfAssessment) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Remarks / Notes</td>
                        <td>{{ $remarks }}</td>
                    </tr>
                </table>

                <h4 style="font-size: 12.5px; font-weight: bold; margin-top: 15px;">CLO Competency Scores Rated by Advisor:</h4>
                <table class="form-table" style="text-align: center;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Course Learning Outcome (CLO)</th>
                            <th style="width: 150px;">Attainment Score (1-4)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: left;">CLO1: Analyse security governance & risk frameworks</td>
                            <td><strong>{{ $scores['clo1'] ?? 3 }} / 4</strong></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">CLO2: Evaluate security applications & justifications</td>
                            <td><strong>{{ $scores['clo2'] ?? 3 }} / 4</strong></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">CLO3: Complete risk lifecycle and control cycles</td>
                            <td><strong>{{ $scores['clo3'] ?? 3 }} / 4</strong></td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">CLO4: Construct organizational plans & policies</td>
                            <td><strong>{{ $scores['clo4'] ?? 3 }} / 4</strong></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 25px; border: 1px dashed #000000; padding: 12px; font-size: 12px; line-height: 1.4;">
                    <strong>Advisor Confirmation Statement:</strong><br>
                    "I hereby confirm that the above student has been advised and is deemed eligible for the APEL(C) assessment paper or portfolio submission."
                </div>

                <div class="signature-grid">
                    <div class="signature-box" style="border: none;"></div>
                    <div class="signature-box">
                        <strong>Advisor Signature</strong><br>
                        {{ $advisorName }} / Faculty Coordinator
                    </div>
                </div>
            @else
                <div style="border: 1px dashed #000000; padding: 15px; text-align: center; font-style: italic; font-size: 13px;">
                    Advisor confirmation and assessment mode selection is currently pending.
                </div>
            @endif

            <div class="print-footer">
                <span>UTM Faculty of Computing APEL(C) Office</span>
                <span>Page 3</span>
            </div>
        </div>

        {{-- PAGE 4: PORTFOLIO SUBMISSION FORM (IF PORTFOLIO ESSAYS EXIST) --}}
        @if(!empty($application->portfolio_essays))
            <div class="print-paper-sheet page-break">
                <div class="print-header">
                    <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                    <p>APEL (C) PORTFOLIO SUBMISSION FORM (LAMPIRAN E)</p>
                </div>

                <h3 class="section-title">PART A: PORTFOLIO METADATA</h3>
                <table class="form-table">
                    <tr>
                        <td class="meta-label">Faculty / School</td>
                        <td>{{ $application->portfolio_essays['school_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Cohort / Year</td>
                        <td>{{ $application->portfolio_essays['cohort'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Course Applied</td>
                        <td>{{ $application->program_applied }}</td>
                    </tr>
                </table>

                <h3 class="section-title">PART C: PORTFOLIO ESSAYS</h3>
                
                <h4 style="font-size:12.5px; font-weight:bold; margin-top:15px; margin-bottom:5px;">Essay 1 (CLO1): Relevant Experience & Knowledge</h4>
                <div class="essay-box">{{ $application->portfolio_essays['essay1'] }}</div>

                <h4 style="font-size:12.5px; font-weight:bold; margin-top:20px; margin-bottom:5px;">Essay 2 (CLO2): Problem Solving & Justification</h4>
                <div class="essay-box">{{ $application->portfolio_essays['essay2'] }}</div>

                <div class="print-footer">
                    <span>UTM Faculty of Computing APEL(C) Office</span>
                    <span>Page 4</span>
                </div>
            </div>

            <div class="print-paper-sheet page-break">
                <div class="print-header">
                    <h2>UNIVERSITI TEKNOLOGI MALAYSIA</h2>
                    <p>APEL (C) PORTFOLIO ESSAYS CONTINUED</p>
                </div>

                <h4 style="font-size:12.5px; font-weight:bold; margin-top:15px; margin-bottom:5px;">Essay 3 (CLO3): Leadership & Responsibility</h4>
                <div class="essay-box">{{ $application->portfolio_essays['essay3'] }}</div>

                <h4 style="font-size:12.5px; font-weight:bold; margin-top:20px; margin-bottom:5px;">Essay 4 (CLO4): Effective Communication & Security Plans</h4>
                <div class="essay-box">{{ $application->portfolio_essays['essay4'] }}</div>

                <h3 class="section-title">DECLARATION OF ORIGINALITY</h3>
                <div style="border: 1px solid #000000; padding: 10px; font-size: 11.5px; line-height: 1.4; margin-bottom: 25px;">
                    I hereby declare that this portfolio compiles authentic evidence of my own experiences and learning. All essays are written by me without unauthorized external assistance.
                </div>

                <div class="signature-grid">
                    <div class="signature-box">
                        <strong>Student Signature</strong><br>
                        {{ $student->name }}
                    </div>
                    <div class="signature-box" style="border: none;"></div>
                </div>

                <div class="print-footer">
                    <span>UTM Faculty of Computing APEL(C) Office</span>
                    <span>Page 5</span>
                </div>
            </div>
        @endif
        @endif

    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
