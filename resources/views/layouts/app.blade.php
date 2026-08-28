<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'APEL Management System' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&family=Instrument+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    {{--
        One stylesheet. There used to be four - this file's inline block,
        public/css/app-style.css loaded here, auth.css loaded by five auth
        views, and dashboard.css loaded by three dashboards and two reports.
        The same class was defined in more than one of them with different
        values, so which rule won depended on link order, and the interface
        looked assembled rather than designed. Everything now lives in
        resources/css/app.css, compiled through Vite.

        @stack('styles') is kept for genuinely page-specific rules, and is
        loaded AFTER the system so a page can extend it - but no page should
        be redefining a shared primitive there.
    --}}
    @vite(['resources/css/app.css'])
</head>

<body>
    @php
        $onAuthScreen = request()->routeIs('login', 'register', '2fa.*', 'password.*');
        $role = auth()->check() ? auth()->user()->role : null;
        $navLinks = match ($role) {
            'student' => [
                ['route' => 'student.dashboard', 'label' => 'Dashboard'],
                ['route' => 'student.applications.index', 'label' => 'My Applications'],
                ['route' => 'student.applications.create', 'label' => 'New Application'],
            ],
            'evaluator' => [
                ['route' => 'evaluator.dashboard', 'label' => 'Dashboard'],
                ['route' => 'evaluator.applications.index', 'label' => 'Assigned'],
                ['route' => 'evaluator.assessment.papers.index', 'label' => 'Papers'],
                ['route' => 'evaluator.assessment.grading.index', 'label' => 'Grading'],
            ],
            'admin' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
                ['route' => 'admin.applications.index', 'label' => 'Applications'],
                ['route' => 'admin.apel_a.index', 'label' => 'APEL A'],
                ['route' => 'admin.reports.apel_a', 'label' => 'Reports'],
            ],
            default => [],
        };
    @endphp

    @unless ($onAuthScreen)
        <header class="navbar">
            <div class="nav-brand">
                <span class="nav-mark" aria-hidden="true">APEL</span>
                <h1>Accreditation of Prior Experiential Learning</h1>
            </div>

            @auth
                <nav class="nav-primary" aria-label="Main">
                    @foreach ($navLinks as $link)
                        @if (Route::has($link['route']))
                            <a href="{{ route($link['route']) }}"
                               @class(['nav-item', 'is-current' => request()->routeIs($link['route'])])
                               @if (request()->routeIs($link['route'])) aria-current="page" @endif>
                                {{ $link['label'] }}
                            </a>
                        @endif
                    @endforeach
                </nav>

                <div class="nav-links">
                    <span class="nav-identity">
                        {{ auth()->user()->name }}
                        <em class="nav-role">{{ ucfirst(auth()->user()->role) }}</em>
                    </span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit">Sign out</button>
                    </form>
                </div>
            @endauth
        </header>
    @endunless

    <main>
        @yield('content')
    </main>

    @stack('scripts')

    @if (session('success') || session('error'))
        @php $isError = (bool) session('error'); @endphp
        <div id="toast-notification-container" role="status" aria-live="polite" aria-atomic="true">
            <div class="toast-card {{ $isError ? 'is-error' : 'is-success' }}">
                <div class="toast-body">
                    <strong>{{ $isError ? 'Error' : 'Success' }}</strong>
                    <span>{{ session('error') ?? session('success') }}</span>
                </div>
                <button type="button" class="toast-dismiss" aria-label="Dismiss notification">&times;</button>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toast = document.querySelector('.toast-card');
                if (!toast) return;
                const hide = () => toast.classList.remove('is-visible');
                setTimeout(() => toast.classList.add('is-visible'), 120);
                setTimeout(hide, 6000);
                const btn = toast.querySelector('.toast-dismiss');
                if (btn) btn.addEventListener('click', hide);
            });
        </script>
        {{--
            The blocking alert() that used to fire here for draft saves duplicated
            this toast, forcing the user to dismiss the same message twice.
        --}}
    @endif
</body>

</html>
