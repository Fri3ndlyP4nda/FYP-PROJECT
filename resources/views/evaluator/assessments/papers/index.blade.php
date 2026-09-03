@extends('layouts.app')

@section('content')
    {{--
        The papers this evaluator has written.

        A library, not a queue: nothing here is waiting on anyone. So it is a
        plain list rather than the grouped-by-whose-move layout used everywhere
        an evaluator has work to do - the shape should tell them which kind of
        screen they are on before they read a word.
    --}}
    <div class="deck">
        <header class="deck-head">
            <div>
                <p class="deck-eyebrow">Assessment papers</p>
                <h1 class="deck-title">
                    {{ $papers->count() }} {{ Str::plural('paper', $papers->count()) }}
                </h1>
            </div>
            <div class="deck-acts">
                <a href="{{ route('evaluator.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                <a href="{{ route('evaluator.assessment.grading.index') }}" class="btn btn-secondary">Grading</a>
            </div>
        </header>

        @if (session('success'))
            <p class="notice notice--good" role="status">{{ session('success') }}</p>
        @endif

        @if ($papers->isEmpty())
            <section class="blank">
                <h2>You have not written a paper yet.</h2>
                <p>
                    A paper is set against one APEL C application. Open an application you are
                    assigned to and set its assessment from there.
                </p>
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">Your applications</a>
            </section>
        @else
            <section class="stack">
                <div class="stack-body stack-body--rows">
                    @foreach ($papers as $paper)
                        <article class="row-case">
                            <div class="row-case-tell">
                                <div class="case-top">
                                    <span class="badge badge--{{ $paper->status === 'active' ? 'good' : 'neutral' }}">
                                        {{ $paper->status === 'active' ? 'In use' : ucfirst((string) $paper->status) }}
                                    </span>
                                    <span class="case-ref">{{ strtoupper(substr((string) $paper->_id, -6)) }}</span>
                                </div>

                                <h3 class="row-case-title">{{ $paper->title ?: 'Untitled paper' }}</h3>

                                <p class="row-case-meta">
                                    @if ($paper->created_at)
                                        Written {{ \Carbon\Carbon::parse($paper->created_at)->format('j M Y') }}
                                    @else
                                        Date not recorded
                                    @endif
                                </p>

                                @if ($paper->question_file)
                                    <p class="row-case-meta">
                                        <a href="{{ route('files.paper', $paper->_id) }}"
                                           target="_blank" rel="noopener">Open the paper</a>
                                    </p>
                                @endif
                            </div>

                            @if ($paper->application_id && Route::has('evaluator.applications.show'))
                                <a class="btn btn-ghost btn--sm"
                                   href="{{ route('evaluator.applications.show', $paper->application_id) }}">
                                    The application
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
