@extends('layouts.app')

@section('content')
    <div class="container app-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">APEL A Result</span>
                <h2>APEL A Application Details</h2>
                <p class="muted page-hero-text">
                    Review your APEL A admission progress, evaluator recommendation, and final admin decision.
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
                    <h3>APEL A Workflow Status</h3>
                </div>

                <div>
                    <span class="badge badge-pending">
                        Current Status: {{ $application->status ?? 'Pre-Application Submitted' }}
                    </span>
                </div>
            </div>

            @php
                $currentStatus = $application->status ?? 'Pre-Application Submitted';

                if (($application->final_decision ?? null) === 'rejected' || $currentStatus === 'Final Rejected') {
                    $steps = [
                        'Pre-Application Submitted',
                        'Under Advisor Review',
                        'Advisor Approved',
                        'Assessment In Progress',
                        'Final Rejected',
                    ];
                } else {
                    $steps = [
                        'Pre-Application Submitted',
                        'Under Advisor Review',
                        'Advisor Approved',
                        'Assessment In Progress',
                        'Final Approved',
                    ];
                }

                if ($currentStatus === 'Advisor Rejected') {
                    $steps = ['Pre-Application Submitted', 'Under Advisor Review', 'Advisor Rejected'];
                    $currentIndex = 2;
                } else {
                    $currentIndex = match ($currentStatus) {
                        'Pre-Application Submitted' => 0,
                        'Under Advisor Review' => 1,
                        
                        'Advisor Approved',
                        'Payment Submitted',
                        'Payment Verified',
                        'Payment Rejected',
                        'Payment Pending' => 2,
                        
                        'Assessor Assigned',
                        'Evaluator Assigned',
                        'Assessment In Progress',
                        'Awaiting Final Decision' => 3,
                        
                        'Final Approved',
                        'Final Rejected' => 4,
                        
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
                    @if (($application->final_decision ?? 'pending') === 'approved')
                        <span class="badge badge-approved">Final Decision: Approved</span>
                    @elseif (($application->final_decision ?? 'pending') === 'rejected')
                        <span class="badge badge-rejected">Final Decision: Rejected</span>
                    @else
                        <span class="badge badge-pending">Final Decision Pending</span>
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
                    <span class="meta-label">Review Stage</span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $application->review_stage ?? 'submitted')) }}</strong>
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
                <div class="record-panel" style="grid-column: span 2;">
                    <h4>Evaluator Recommendation</h4>
                    <p class="feedback-text">
                        {{ ucfirst(str_replace('_', ' ', $application->admission_decision ?? 'pending')) }}
                    </p>
                </div>
            </div>

            <div class="record-body-grid">
                <div class="record-panel">
                    <h4>Final Admin Decision</h4>
                    <p class="feedback-text">
                        {{ ucfirst(str_replace('_', ' ', $application->final_decision ?? 'pending')) }}
                    </p>
                </div>

                <div class="record-panel">
                    <h4>Final Admin Remarks</h4>
                    <p class="feedback-text">
                        {{ $application->final_decision_remarks ?? 'No final admin remarks available yet.' }}
                    </p>
                </div>
            </div>

            <div class="record-panel">
                <h4>Evaluator Feedback</h4>
                <p class="feedback-text">
                    {{ $application->evaluator_feedback ?? 'No evaluator feedback available yet.' }}
                </p>
            </div>

            <div class="record-panel" style="margin-top: 20px;">
                <h4>Applicant Details</h4>
                <p class="feedback-text">
                    <strong>Age:</strong> {{ $application->age ?? 'Not provided' }}<br>
                    <strong>Name of University:</strong> {{ $application->university_name ?? 'Not provided' }}<br>
                    <strong>Name of Company:</strong> {{ $application->company_name ?? 'Not provided' }}
                </p>
            </div>

            <div class="record-panel" style="margin-top: 20px;">
                <h4>Payment Submission</h4>

                <p class="feedback-text">
                    <strong>Payment Type:</strong>
                    {{ $application->payment_type ?? 'APEL A Processing Fee' }}
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

                        <label for="f-payment-receipt">Upload Payment Receipt</label>
                        <input type="file" name="payment_receipt" accept=".pdf,.jpg,.jpeg,.png" required id="f-payment-receipt">

                        <small style="display:block; margin-top:5px; color:#666;">
                            Allowed format: PDF, JPG, JPEG, PNG. Maximum size: 5MB.
                        </small>

                        <label for="f-payment-remarks">Payment Remarks</label>
                        <textarea name="payment_remarks" rows="4" placeholder="Example: Payment completed through PayHub." id="f-payment-remarks">{{ old('payment_remarks', $application->payment_remarks) }}</textarea>

                        <div class="form-submit-row">
                            <button type="submit" class="btn">
                                Submit Payment Receipt
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
