@extends('layouts.app')

@section('content')
    {{--
        Marking one submission.

        The rule this screen applies is unusual and easy to get wrong: a pass
        needs at least 5 of 10 on EVERY outcome, so 10/10/10/4 fails while
        6/6/6/6 passes (AssessmentGradingController:116). The old layout led
        with a running total out of 40 and a percentage - the two numbers the
        rule does not use - so an evaluator could watch "32/40, 80%" climb while
        typing a mark that fails the candidate.

        Each outcome now reports its own verdict as it is typed, and the overall
        line states which outcome is holding it back rather than only the score.
    --}}
    @php
        $isEvaluator1 = (string) ($application->evaluator_id ?? '') === (string) auth()->id();
        $isEvaluator2 = (string) ($application->evaluator_2_id ?? '') === (string) auth()->id();

        $seat = $isEvaluator1 ? 1 : ($isEvaluator2 ? 2 : null);
        $alreadyMarked = $seat !== null && filled($submission->{"evaluator_{$seat}_graded_at"});

        $existing = fn (string $field) => $seat ? $submission->{"evaluator_{$seat}_{$field}"} : null;

        $student = $submission->student_id
            ? \App\Models\User::where('_id', $submission->student_id)->first()
            : null;

        $isPortfolio = ($application->assessment_type ?? '') === 'portfolio';
    @endphp

    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    Marking &nbsp;·&nbsp; {{ strtoupper(substr((string) $submission->_id, -6)) }}
                </p>
                <h1 class="deck-title">{{ $student?->name ?? 'Candidate no longer on file' }}</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-secondary">All submissions</a>
                @if ($application && Route::has('evaluator.applications.show'))
                    <a href="{{ route('evaluator.applications.show', $application->_id) }}" class="btn btn-secondary">
                        The application
                    </a>
                @endif
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="notice notice--bad" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- What is being marked. Reading it comes before scoring it. --}}
        <section class="panel" aria-labelledby="work-head">
            <h2 class="panel-head" id="work-head">The work</h2>

            <dl class="kv">
                <div>
                    <dt>Course</dt>
                    <dd>
                        {{ $application?->credit_course_name
                            ?: ($application?->credit_course_code ?: 'Not stated') }}
                    </dd>
                </div>
                <div><dt>Assessed by</dt><dd>{{ $isPortfolio ? 'Portfolio' : 'Written paper' }}</dd></div>
                <div>
                    <dt>Submitted</dt>
                    <dd>
                        {{ $submission->submitted_at
                            ? \Carbon\Carbon::parse($submission->submitted_at)->format('j M Y, H:i')
                            : 'Not recorded' }}
                    </dd>
                </div>
            </dl>

            @if ($submission->answer_file)
                <p class="note">
                    <a href="{{ route('files.submission', $submission->_id) }}" target="_blank" rel="noopener">
                        Open the answer script
                    </a>
                </p>
            @endif

            @if ($application && filled($application->portfolio_file))
                <ul class="files">
                    @foreach ((array) $application->portfolio_file as $file)
                        @php
                            $path = is_array($file) ? ($file['path'] ?? '') : (string) $file;
                            $name = is_array($file) ? ($file['name'] ?? basename($path)) : basename($path);
                        @endphp
                        @continue($path === '')
                        <li>
                            <span class="files-kind">Portfolio</span>
                            <a href="{{ route('files.application', ['application' => $application->_id, 'path' => $path]) }}"
                               target="_blank" rel="noopener">{{ $name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($alreadyMarked)
            <section class="panel" aria-labelledby="done-head">
                <h2 class="panel-head" id="done-head">You have already marked this</h2>

                <p class="outcome outcome--{{ $existing('result') === 'pass' ? 'good' : 'bad' }}">
                    {{ $existing('result') === 'pass' ? 'Passed' : 'Not passed' }}
                    @if (filled($existing('score')))
                        &nbsp;·&nbsp; {{ $existing('score') }}%
                    @endif
                </p>

                <ul class="clo-list">
                    @foreach ([1, 2, 3, 4] as $i)
                        @php $mark = (int) $existing("clo{$i}"); @endphp
                        <li class="clo {{ $mark >= 5 ? 'is-pass' : 'is-fail' }}">
                            <span class="clo-name">CLO{{ $i }}</span>
                            <span class="clo-mark">{{ $mark }}<span>/10</span></span>
                        </li>
                    @endforeach
                </ul>

                @if (filled($existing('feedback')))
                    <div class="said">
                        <h3>Your feedback</h3>
                        <p>{{ $existing('feedback') }}</p>
                    </div>
                @endif

                <p class="muted">
                    A mark cannot be changed here. If it is wrong, the registry can reopen it.
                </p>
            </section>
        @else
            <section class="panel" aria-labelledby="mark-head">
                <h2 class="panel-head" id="mark-head">Your marks</h2>

                <p class="clo-rule">
                    Each outcome is marked out of 10, and a pass needs at least 5 on
                    <strong>every</strong> one of them. A high total does not carry a single
                    outcome below 5.
                </p>

                <form method="POST" action="{{ route('evaluator.assessment.grading.grade', $submission->_id) }}"
                      id="mark-form" class="stack-form">
                    @csrf

                    <div class="marks">
                        @foreach ([1, 2, 3, 4] as $i)
                            <div class="mark" data-mark>
                                <label for="f-clo{{ $i }}">Outcome {{ $i }}</label>
                                <div class="mark-row">
                                    <input type="number" id="f-clo{{ $i }}" name="clo{{ $i }}"
                                           min="0" max="10" step="1" required inputmode="numeric"
                                           value="{{ old('clo'.$i) }}" data-score>
                                    <span class="mark-of">/ 10</span>
                                    <span class="mark-verdict" data-verdict aria-live="polite"></span>
                                </div>
                                <x-field-error name="clo{{ $i }}" />
                            </div>
                        @endforeach
                    </div>

                    {{--
                        The overall line names the outcome that is failing, which is
                        the thing the marker needs to notice. Announced politely so
                        it is not read out on every keystroke.
                    --}}
                    <p class="verdict-line" id="overall" role="status" aria-live="polite"></p>

                    <div class="field">
                        <label for="f-grader-feedback">Feedback for the candidate</label>
                        <textarea id="f-grader-feedback" name="grader_feedback" rows="5"
                                  maxlength="1000"
                                  placeholder="What they demonstrated, and where the evidence fell short.">{{ old('grader_feedback') }}</textarea>
                        <p class="field-hint">Shown to the candidate with their result. Up to 1000 characters.</p>
                        <x-field-error name="grader_feedback" />
                    </div>

                    <button type="submit" class="btn btn-primary">Submit marks</button>
                </form>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('mark-form');
            if (!form) return;

            const overall = document.getElementById('overall');
            const blocks = [...form.querySelectorAll('[data-mark]')];

            // Mirrors AssessmentGradingController:116 - every outcome must reach 5.
            const PASS_MARK = 5;

            function paint() {
                const marks = blocks.map((block, i) => {
                    const input = block.querySelector('[data-score]');
                    const verdict = block.querySelector('[data-verdict]');
                    const raw = input.value.trim();
                    const n = raw === '' ? null : Number(raw);

                    block.classList.remove('is-pass', 'is-fail');
                    verdict.textContent = '';

                    if (n === null || Number.isNaN(n)) return { i: i + 1, n: null };

                    const ok = n >= PASS_MARK;
                    block.classList.add(ok ? 'is-pass' : 'is-fail');
                    verdict.textContent = ok ? 'meets it' : 'below 5';
                    return { i: i + 1, n };
                });

                const given = marks.filter(m => m.n !== null);

                if (given.length < marks.length) {
                    overall.className = 'verdict-line';
                    overall.textContent = `${given.length} of ${marks.length} outcomes marked.`;
                    return;
                }

                const failing = given.filter(m => m.n < PASS_MARK).map(m => 'outcome ' + m.i);
                const total = given.reduce((sum, m) => sum + m.n, 0);

                if (failing.length === 0) {
                    overall.className = 'verdict-line is-pass';
                    overall.textContent = `Pass — every outcome is at or above 5. Total ${total} of 40.`;
                } else {
                    overall.className = 'verdict-line is-fail';
                    overall.textContent =
                        `Fail — ${failing.join(' and ')} ${failing.length === 1 ? 'is' : 'are'} below 5. ` +
                        `Total ${total} of 40 does not change that.`;
                }
            }

            form.addEventListener('input', paint);
            paint();
        })();
    </script>
@endpush
