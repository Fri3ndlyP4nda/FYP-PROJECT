@extends('layouts.app')

@section('content')
    {{--
        The shared shape for every error page.

        A person meeting one of these has already failed at something and is
        usually mid-task. The page owes them three things and nothing else:
        what happened in plain words, whether it was their fault, and one
        obvious way onward. No apology, no jargon, no status code shouted in
        96px - the number is at the bottom, small, for whoever they end up
        reporting it to.
    --}}
    <div class="gate">
        <a class="gate-mark" href="{{ url('/') }}">
            <span class="door-mark-glyph" aria-hidden="true">AP</span>
            <span class="door-mark-name">APEL</span>
        </a>

        <section class="door-panel" aria-labelledby="err-head">
            <h2 id="err-head">@yield('title')</h2>
            <p class="door-panel-sub">@yield('message')</p>

            @hasSection('detail')
                <p class="note">@yield('detail')</p>
            @endif

            <div class="door-foot">
                @yield('ways')
            </div>

            <p class="err-code">@yield('code')</p>
        </section>
    </div>
@endsection
