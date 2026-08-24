@extends('layouts.app')

@section('content')
    <div class="container grading-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">APEL C Grading</span>
                <h2>Grade Submission</h2>
                <p class="muted page-hero-text">
                    Review the uploaded answer file, assign an overall score, and provide grading feedback.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-secondary">Back to Grading List</a>
            </div>
        </section>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grading-layout">
            <div class="grading-main">
                <div class="question-card">
                    <div class="question-header">
                        <span class="question-number">Submission File</span>
                        <span class="question-type">APEL C Answer Upload</span>
                    </div>

                    <div class="record-meta-grid">
                        <div class="meta-box">
                            <span class="meta-label">Application ID</span>
                            <strong>{{ $submission->application_id }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Student</span>
                            <strong>{{ \App\Models\User::where('_id', $submission->student_id)->value('name') ?? 'Unknown' }}</strong>
                        </div>

                        <div class="meta-box">
                            <span class="meta-label">Submitted At</span>
                            <strong>{{ $submission->submitted_at ?? 'Not available' }}</strong>
                        </div>
                    </div>

                    <div class="record-panel">
                        <h4>Uploaded Answer / Portfolio File(s)</h4>

                        @if ($submission->answer_file)
                            <a href="{{ asset('storage/' . $submission->answer_file) }}" target="_blank"
                                class="paper-file-link">
                                Open Submitted Answer
                            </a>
                        @elseif (($application->assessment_type ?? '') === 'portfolio' && !empty($application->portfolio_file))
                            <p class="feedback-text" style="font-weight:600; margin-bottom:8px;">Student Portfolio Files:</p>
                            <ul style="margin: 0; padding-left: 20px;">
                                @foreach ($application->portfolio_file as $file)
                                    @php
                                        $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
                                        $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
                                    @endphp
                                    <li style="margin-bottom: 6px;">
                                        <a href="{{ asset('storage/' . $filePath) }}" target="_blank" style="color: #8B1E3F; font-weight: 600; text-decoration: underline;">
                                            {{ $fileName }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="feedback-text">No answer file or portfolio uploaded for this submission.</p>
                        @endif
                    </div>

                    <div class="record-panel">
                        <h4>Current Grading Status</h4>
                        <p class="feedback-text">
                            @if ($submission->graded_at)
                                This submission has already been graded.
                            @else
                                This submission is waiting for evaluator grading.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

        <aside class="grading-side">
            @php
                $isEvaluator1 = (string) ($application->evaluator_id ?? '') === (string) Auth::id();
                $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) Auth::id();
                
                $hasGradedThisUser = false;
                $existingScore = null;
                $existingFeedback = null;
                $existingResult = null;
                $existingClo1 = null;
                $existingClo2 = null;
                $existingClo3 = null;
                $existingClo4 = null;
                
                if ($isEvaluator1 && !empty($submission->evaluator_1_graded_at)) {
                    $hasGradedThisUser = true;
                    $existingScore = $submission->evaluator_1_score;
                    $existingFeedback = $submission->evaluator_1_feedback;
                    $existingResult = $submission->evaluator_1_result;
                    $existingClo1 = $submission->evaluator_1_clo1;
                    $existingClo2 = $submission->evaluator_1_clo2;
                    $existingClo3 = $submission->evaluator_1_clo3;
                    $existingClo4 = $submission->evaluator_1_clo4;
                } elseif ($isEvaluator2 && !empty($submission->evaluator_2_graded_at)) {
                    $hasGradedThisUser = true;
                    $existingScore = $submission->evaluator_2_score;
                    $existingFeedback = $submission->evaluator_2_feedback;
                    $existingResult = $submission->evaluator_2_result;
                    $existingClo1 = $submission->evaluator_2_clo1;
                    $existingClo2 = $submission->evaluator_2_clo2;
                    $existingClo3 = $submission->evaluator_2_clo3;
                    $existingClo4 = $submission->evaluator_2_clo4;
                }
            @endphp

            <div class="card grading-summary" style="padding: 20px; border-radius: 12px; background: #ffffff; border: 1px solid #e5e7eb;">
                <h3 style="color: #8B1E3F; margin-top: 0; margin-bottom: 15px; font-size: 17px;">APEL (C) Portfolio Scoring Rubrics</h3>

                <form method="POST" action="{{ route('evaluator.assessment.grading.grade', $submission->_id) }}">
                    @csrf

                    <!-- CLO 1 -->
                    <div style="margin-bottom: 18px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;">
                        <label for="f-clo1" style="font-weight: 700; font-size: 13px; display: block; color: #1f2937; margin-bottom: 4px;">CLO 1 Score (0 - 10)</label>
                        <p style="font-size: 11px; color: #6b7280; margin-bottom: 8px; line-height: 1.3;">
                            Analyze IT security frameworks/standards: <strong>0-1</strong> (1 evidence / Fail), <strong>2-4</strong> (2 evidences), <strong>5-7</strong> (3 evidences), <strong>8-10</strong> (4+ evidences).
                        </p>
                        <input type="number" name="clo1" class="clo-score-input" min="0" max="10" 
                            value="{{ old('clo1', $hasGradedThisUser ? $existingClo1 : '') }}" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;" {{ $hasGradedThisUser ? 'disabled' : '' }} id="f-clo1">
                        <x-field-error name="clo1" />
                    </div>

                    <!-- CLO 2 -->
                    <div style="margin-bottom: 18px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;">
                        <label for="f-clo2" style="font-weight: 700; font-size: 13px; display: block; color: #1f2937; margin-bottom: 4px;">CLO 2 Score (0 - 10)</label>
                        <p style="font-size: 11px; color: #6b7280; margin-bottom: 8px; line-height: 1.3;">
                            Evaluate security & management applications: <strong>0-1</strong> (1 evidence of tools / Fail), <strong>2-4</strong> (2 evidences), <strong>5-7</strong> (3 evidences), <strong>8-10</strong> (4 evidences).
                        </p>
                        <input type="number" name="clo2" class="clo-score-input" min="0" max="10" 
                            value="{{ old('clo2', $hasGradedThisUser ? $existingClo2 : '') }}" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;" {{ $hasGradedThisUser ? 'disabled' : '' }} id="f-clo2">
                        <x-field-error name="clo2" />
                    </div>

                    <!-- CLO 3 -->
                    <div style="margin-bottom: 18px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;">
                        <label for="f-clo3" style="font-weight: 700; font-size: 13px; display: block; color: #1f2937; margin-bottom: 4px;">CLO 3 Score (0 - 10)</label>
                        <p style="font-size: 11px; color: #6b7280; margin-bottom: 8px; line-height: 1.3;">
                            Complete risk identification cycle: <strong>0-1</strong> (1 evidence of strategies / Fail), <strong>2-4</strong> (2 evidences), <strong>5-7</strong> (3 evidences), <strong>8-10</strong> (4+ evidences).
                        </p>
                        <input type="number" name="clo3" class="clo-score-input" min="0" max="10" 
                            value="{{ old('clo3', $hasGradedThisUser ? $existingClo3 : '') }}" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;" {{ $hasGradedThisUser ? 'disabled' : '' }} id="f-clo3">
                        <x-field-error name="clo3" />
                    </div>

                    <!-- CLO 4 -->
                    <div style="margin-bottom: 18px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;">
                        <label for="f-clo4" style="font-weight: 700; font-size: 13px; display: block; color: #1f2937; margin-bottom: 4px;">CLO 4 Score (0 - 10)</label>
                        <p style="font-size: 11px; color: #6b7280; margin-bottom: 8px; line-height: 1.3;">
                            Construct organization-wide security plans: <strong>0-1</strong> (1 evidence of skills / Fail), <strong>2-4</strong> (2 evidences), <strong>5-7</strong> (3 evidences), <strong>8-10</strong> (4+ evidences).
                        </p>
                        <input type="number" name="clo4" class="clo-score-input" min="0" max="10" 
                            value="{{ old('clo4', $hasGradedThisUser ? $existingClo4 : '') }}" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;" {{ $hasGradedThisUser ? 'disabled' : '' }} id="f-clo4">
                        <x-field-error name="clo4" />
                    </div>

                    <!-- Calculator Output Panel -->
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-bottom: 15px;">
                        <h4 style="margin-top:0; margin-bottom:8px; font-size:12.5px; color:#4b5563; text-transform:uppercase; letter-spacing:0.5px;">Live Scoring Summary</h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                            <span>Total CLO Score:</span>
                            <strong id="total_score_text">0 / 40</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                            <span>Overall Percentage:</span>
                            <strong id="percentage_text">0%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #d1d5db; padding-top: 6px; font-size: 14px; font-weight: bold;">
                            <span>Calculated Result:</span>
                            <span id="result_badge" class="badge" style="padding: 2px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; font-weight: bold;">Awaiting Scores</span>
                        </div>
                    </div>

                    <label for="f-grader-feedback" style="font-weight: 700; font-size: 13px; display: block; color: #1f2937; margin-bottom: 4px;">Grader Feedback</label>
                    <textarea name="grader_feedback" rows="4" placeholder="Write your grading comments here..." {{ $hasGradedThisUser ? 'readonly' : '' }} style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; margin-bottom: 15px;" id="f-grader-feedback">{{ old('grader_feedback', $hasGradedThisUser ? $existingFeedback : '') }}</textarea>
                    <x-field-error name="grader_feedback" />

                    <div class="tip-box tip-box-light" style="margin-top: 0; margin-bottom: 14px; background: #fefbeb; border-left: 4px solid #f59e0b; padding: 10px; font-size: 11.5px; border-radius: 4px; line-height: 1.4; color: #856404;">
                        <strong>UTM APEL C Rules:</strong>
                        <p style="margin: 3px 0 0 0;">
                            Student must score <strong>at least 5 / 10 (50%)</strong> on each of the 4 Course Learning Outcomes to obtain an overall **PASS** recommendation.
                        </p>
                    </div>

                    @if ($hasGradedThisUser)
                        <div class="alert alert-success" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb; padding: 10px; border-radius: 8px; font-weight: 600; text-align: center; margin-top: 10px; font-size: 13px;">
                            ✓ Your Grading Completed ({{ strtoupper($existingResult) }})
                        </div>
                    @else
                        <button type="submit" class="btn btn-full" style="width: 100%; padding: 10px; background: #8B1E3F; color: #ffffff; font-weight: 600; border: none; border-radius: 6px; cursor: pointer;">
                            Save Grade
                        </button>
                    @endif
                </form>
            </div>
        </aside>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll(".clo-score-input");
        const totalText = document.getElementById("total_score_text");
        const percentageText = document.getElementById("percentage_text");
        const resultBadge = document.getElementById("result_badge");

        function updateGrades() {
            let total = 0;
            let allFilled = true;
            let passEach = true;

            inputs.forEach(input => {
                const valStr = input.value;
                if (valStr === "") {
                    allFilled = false;
                    return;
                }
                const val = parseInt(valStr, 10);
                total += val;
                if (val < 5) {
                    passEach = false;
                }
            });

            if (!allFilled) {
                totalText.textContent = "-- / 40";
                percentageText.textContent = "--%";
                resultBadge.textContent = "Awaiting Scores";
                resultBadge.style.background = "#e5e7eb";
                resultBadge.style.color = "#4b5563";
                return;
            }

            const pct = Math.round((total / 40) * 100);
            totalText.textContent = total + " / 40";
            percentageText.textContent = pct + "%";

            if (passEach) {
                resultBadge.textContent = "PASS";
                resultBadge.style.background = "#d1fae5";
                resultBadge.style.color = "#065f46";
            } else {
                resultBadge.textContent = "FAIL";
                resultBadge.style.background = "#fee2e2";
                resultBadge.style.color = "#991b1b";
            }
        }

        inputs.forEach(input => {
            input.addEventListener("input", updateGrades);
        });

        updateGrades();
    });
</script>
@endsection
