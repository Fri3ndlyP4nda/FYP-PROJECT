@extends('layouts.app')

@section('content')
    {{--
        Setting the assessment for one APEL C application.

        Two ways in - reuse a paper already written, or upload a new one - so
        the choice comes first and only the fields belonging to it are shown.

        The old version hid its radios with inline display:none and toggled
        sections from an onchange attribute, which left the control unreachable
        by keyboard. These are real radios in a fieldset, and the panels toggle
        with the hidden property rather than inline styles, so nothing depends
        on a script having run to be operable: with JavaScript off both panels
        are visible and the server still enforces required_if.
    --}}
    <div class="deck deck--narrow">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">
                    APEL C &nbsp;·&nbsp; {{ strtoupper(substr((string) $application->_id, -6)) }}
                </p>
                <h1 class="deck-title">Set the assessment</h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('evaluator.applications.show', $application->_id) }}" class="btn btn-secondary">
                    The application
                </a>
                <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn btn-secondary">Your papers</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="notice notice--bad" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="panel" aria-labelledby="for-head">
            <h2 class="panel-head" id="for-head">What this is for</h2>
            <dl class="kv">
                <div>
                    <dt>Course</dt>
                    <dd>
                        {{ $application->credit_course_name
                            ?: ($application->credit_course_code ?: 'Not stated') }}
                    </dd>
                </div>
                <div>
                    <dt>Candidate</dt>
                    <dd>
                        {{ \App\Models\User::where('_id', $application->user_id)->value('name') ?: 'Not on file' }}
                    </dd>
                </div>
            </dl>
        </section>

        <form method="POST" action="{{ route('evaluator.assessment.papers.store', $application->_id) }}"
              enctype="multipart/form-data" id="paper-form">
            @csrf

            <section class="panel" aria-labelledby="source-head">
                <h2 class="panel-head" id="source-head">Where the paper comes from</h2>

                <fieldset class="picker">
                    <legend class="sr-only">Choose a source for this assessment paper</legend>

                    <label class="pick">
                        <input type="radio" name="paper_source" value="library"
                               {{ old('paper_source', 'library') === 'library' ? 'checked' : '' }}
                               data-source>
                        <span>
                            <strong>Reuse one you have written</strong>
                            <small>{{ $libraryPapers->count() }} in your library</small>
                        </span>
                    </label>

                    <label class="pick">
                        <input type="radio" name="paper_source" value="upload"
                               {{ old('paper_source') === 'upload' ? 'checked' : '' }}
                               data-source>
                        <span>
                            <strong>Upload a new one</strong>
                            <small>PDF, up to 10MB</small>
                        </span>
                    </label>
                </fieldset>
                <x-field-error name="paper_source" />
            </section>

            <section class="panel" data-panel="library" aria-labelledby="lib-head">
                <h2 class="panel-head" id="lib-head">From your library</h2>

                @if ($libraryPapers->isEmpty())
                    <p class="muted">
                        You have not written a paper yet. Choose <strong>Upload a new one</strong> above.
                    </p>
                @else
                    <div class="field">
                        <label for="library_paper_select">Which paper</label>
                        <select name="library_paper_id" id="library_paper_select">
                            <option value="">Choose one</option>
                            @foreach ($libraryPapers as $paper)
                                <option value="{{ $paper->_id }}"
                                        {{ old('library_paper_id') === (string) $paper->_id ? 'selected' : '' }}>
                                    {{ $paper->title }}
                                </option>
                            @endforeach
                        </select>
                        <x-field-error name="library_paper_id" />
                    </div>
                @endif
            </section>

            <section class="panel" data-panel="upload" aria-labelledby="up-head">
                <h2 class="panel-head" id="up-head">A new paper</h2>

                <div class="field">
                    <label for="upload_title">Title</label>
                    <input type="text" name="title" id="upload_title" maxlength="255"
                           value="{{ old('title') }}" placeholder="For example: Written assessment, Semester 1">
                    <x-field-error name="title" />
                </div>

                <div class="field">
                    <label for="upload_file">Question paper</label>
                    <input type="file" name="question_file" id="upload_file" accept=".pdf">
                    <p class="field-hint">PDF only, up to 10MB.</p>
                    <x-field-error name="question_file" />
                </div>
            </section>

            <section class="panel" aria-labelledby="both-head">
                <h2 class="panel-head" id="both-head">Instructions and deadline</h2>

                <div class="field">
                    <label for="paper_instructions">Instructions for the candidate</label>
                    <textarea name="instructions" id="paper_instructions" rows="6"
                              placeholder="What to answer, and how to present it.">{{ old('instructions') }}</textarea>
                    <x-field-error name="instructions" />
                </div>

                <div class="field">
                    <label for="paper_deadline">Submission deadline</label>
                    <input type="datetime-local" name="submission_deadline" id="paper_deadline" required
                           value="{{ old('submission_deadline') }}"
                           min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                    {{-- The server rejects anything not `after:now`, so say so first. --}}
                    <p class="field-hint">Must be in the future. The candidate cannot submit after it passes.</p>
                    <x-field-error name="submission_deadline" />
                </div>

                <button type="submit" class="btn btn-primary">Set this assessment</button>
            </section>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('paper-form');
            if (!form) return;

            const panels = {
                library: form.querySelector('[data-panel="library"]'),
                upload: form.querySelector('[data-panel="upload"]'),
            };

            function show() {
                const picked = form.querySelector('[data-source]:checked');
                const chosen = picked ? picked.value : 'library';

                Object.keys(panels).forEach(function (name) {
                    const panel = panels[name];
                    if (!panel) return;
                    // hidden, not an inline style: the rule stays in CSS and the
                    // panel is hidden from assistive technology too.
                    panel.hidden = name !== chosen;
                });
            }

            form.addEventListener('change', function (e) {
                if (e.target.matches('[data-source]')) show();
            });

            show();
        })();
    </script>
@endpush
