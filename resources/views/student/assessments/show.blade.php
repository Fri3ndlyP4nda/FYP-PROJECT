@extends('layouts.app')

@section('content')
    <div class="container app-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">APEL C Assessment</span>
                <h2>Assessment Submission</h2>
                <p class="muted page-hero-text">
                    Review the assessment paper, follow the instructions, and upload your answer file for evaluator grading.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('student.applications.index') }}" class="btn btn-secondary">Back to Applications</a>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-split-layout">
            <div class="card form-main-card">
                <div class="record-meta-grid">
                    <div class="meta-box">
                        <span class="meta-label">Application Type</span>
                        <strong>{{ $application->application_type }}</strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Program Applied</span>
                        <strong>{{ $application->program_applied }}</strong>
                    </div>

                    <div class="meta-box">
                        <span class="meta-label">Assessment Status</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $application->credit_status ?? 'awaiting_assessment')) }}</strong>
                    </div>
                </div>

                <div class="record-panel" style="margin-bottom: 18px;">
                    <h4>Assessment Paper</h4>

                    @if (!empty($paper))
                        @php
                            $deadline = $paper->submission_deadline ? \Carbon\Carbon::parse($paper->submission_deadline) : null;
                            $isExpired = $deadline ? $deadline->isPast() : false;
                        @endphp

                        @if ($deadline)
                            <div class="tip-box {{ $isExpired ? 'tip-box-danger' : 'tip-box-warning' }}" 
                                 style="margin-top: 15px; margin-bottom: 15px; padding: 14px; border-left: 4px solid {{ $isExpired ? '#ef4444' : '#f59e0b' }}; background: {{ $isExpired ? '#fef2f2' : '#fffbeb' }}; color: {{ $isExpired ? '#991b1b' : '#92400e' }}; border-radius: 8px;">
                                <strong>Submission Deadline</strong>
                                <p style="margin: 4px 0 0 0; font-size: 13.5px; font-weight: 500;">
                                    {{ $deadline->format('d M Y, h:i A') }} ({{ $deadline->diffForHumans() }})
                                </p>
                                @if (!$isExpired && empty($submission))
                                    <div id="countdown-timer" style="margin-top: 8px; font-weight: 700; font-size: 14px; color: #b45309;" data-deadline="{{ $deadline->toIso8601String() }}">
                                        Time Remaining: Loading...
                                    </div>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const timerEl = document.getElementById('countdown-timer');
                                            const deadline = new Date(timerEl.getAttribute('data-deadline')).getTime();
                                            
                                            const interval = setInterval(function() {
                                                const now = new Date().getTime();
                                                const diff = deadline - now;
                                                
                                                if (diff <= 0) {
                                                    clearInterval(interval);
                                                    timerEl.innerHTML = "Time Remaining: EXPIRED";
                                                    window.location.reload();
                                                    return;
                                                }
                                                
                                                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                                
                                                let timerText = "Time Remaining: ";
                                                if (days > 0) timerText += days + "d ";
                                                timerText += hours + "h " + minutes + "m " + seconds + "s";
                                                timerEl.innerHTML = timerText;
                                            }, 1000);
                                        });
                                    </script>
                                @endif
                            </div>
                        @endif

                        <!-- Assessment Paper Details & Download -->
                        <div class="paper-details-card" style="background: rgba(139, 30, 63, 0.03); border: 1px solid rgba(139, 30, 63, 0.15); border-radius: 12px; padding: 20px; margin-top: 15px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                                <div style="background: #8B1E3F; color: white; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">
                                    PDF
                                </div>
                                <div>
                                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #8B1E3F; display: block;">Assessment Paper Title</span>
                                    <h4 style="margin: 2px 0 0 0; font-size: 16px; font-weight: 700; color: #1f2937; line-height: 1.3;">{{ $paper->title }}</h4>
                                </div>
                            </div>

                            @if (!empty($paper->instructions))
                                <div style="margin-bottom: 16px;">
                                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; display: block; margin-bottom: 4px;">Instructions</span>
                                    <div style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.6; color: #4b5563; background: white; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">{{ $paper->instructions }}</div>
                                </div>
                            @endif

                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 10px; border-top: 1px dashed rgba(139, 30, 63, 0.1);">
                                <div>
                                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; display: block; margin-bottom: 2px;">Format</span>
                                    <strong style="font-size: 13px; color: #374151;">PDF Document</strong>
                                </div>
                                <a href="{{ asset('storage/' . $paper->question_file) }}" target="_blank" class="btn" style="padding: 10px 18px; font-size: 13.5px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="display: inline-block; vertical-align: middle;">
                                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                    </svg>
                                    Download Assessment Paper
                                </a>
                            </div>
                        </div>

                        @if (empty($submission) || empty($submission->answer_file))
                            @if ($isExpired)
                                <div style="background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; padding: 15px; border-radius: 12px; margin-top: 15px; font-size: 13.5px;">
                                    <strong>Submission Closed</strong>
                                    <p style="margin: 5px 0 0 0;">The deadline to submit your answer file has passed. You can no longer upload answers for this assessment.</p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('student.assessment.submit', $application->_id) }}"
                                    enctype="multipart/form-data" style="margin-top: 18px;">
                                    @csrf

                                    <label>Upload Answer File</label>
                                    <div class="upload-box">
                                        <input type="file" name="answer_file" required>
                                        <p>Upload your completed answer file here.</p>
                                        <small>Use a clear filename before submitting.</small>
                                    </div>

                                    <div class="form-submit-row">
                                        <button type="submit" class="btn">Submit Answer</button>
                                    </div>
                                </form>
                            @endif
                        @else
                            <div class="tip-box tip-box-light" style="margin-top: 18px;">
                                <strong>Submission Completed</strong>
                                <p>
                                    You have already submitted your answer file. You can review your uploaded submission above
                                    while waiting for grading.
                                </p>
                            </div>
                        @endif
                    @else
                        <p class="feedback-text" style="margin-top: 14px;">
                            No assessment paper is available yet.
                        </p>
                    @endif
                </div>

                <div class="record-panel">
                    <h4>Your Submission</h4>

                    @if (!empty($submission) && !empty($submission->answer_file))
                        <div class="record-meta-grid">
                            <div class="meta-box">
                                <span class="meta-label">Submitted File</span>
                                <strong>
                                    @if (!empty($submission->answer_file))
                                        <a href="{{ asset('storage/' . $submission->answer_file) }}" target="_blank"
                                            class="link">
                                            View Uploaded Answer
                                        </a>
                                    @else
                                        No file
                                    @endif
                                </strong>
                            </div>

                            <div class="meta-box">
                                <span class="meta-label">Submitted At</span>
                                <strong>{{ $submission->submitted_at ?? 'Not available' }}</strong>
                            </div>

                            <div class="meta-box">
                                <span class="meta-label">Grading Result</span>
                                <strong>
                                    @if ($submission->result === 'pass')
                                        Pass
                                    @elseif ($submission->result === 'fail')
                                        Fail
                                    @else
                                        Pending
                                    @endif
                                </strong>
                            </div>
                        </div>

                        @if (!empty($submission->grader_feedback))
                            <div class="tip-box tip-box-light" style="margin-top: 14px;">
                                <strong>Grader Feedback</strong>
                                <p>{{ $submission->grader_feedback }}</p>
                            </div>
                        @endif
                    @else
                        <p class="feedback-text" style="margin-bottom: 14px;">
                            You have not submitted your answer yet.
                        </p>
                    @endif
                </div>
            </div>

            <aside class="info-side-card">
                <span class="side-label">Submission Guide</span>
                <h3>Before you submit</h3>

                <ul class="check-list">
                    <li>Read the assessment instructions carefully.</li>
                    <li>Make sure you upload the correct final answer file.</li>
                    <li>Check your file before submission.</li>
                    <li>After grading, the admin will decide the final credit outcome.</li>
                </ul>

                <div class="tip-box">
                    <strong>Tip</strong>
                    <p>
                        Keep a backup copy of your answer file before uploading it to the system.
                    </p>
                </div>
            </aside>
        </div>
    </div>
@endsection
