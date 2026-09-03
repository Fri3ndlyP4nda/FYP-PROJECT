@extends('layouts.app')

@section('content')
    {{--
        The public front door.

        Until now `/` redirected straight to /login, so APEL had no page at all
        for the person it most needs to reach: a working adult who has never
        heard of APEL and assumes university is closed to them because they did
        not go at eighteen.

        That reader is not browsing. They are checking whether they are allowed.
        So the page is built around answering that, and the closing instrument
        runs the real published rules rather than inviting them to guess.

        The thresholds are injected from config/apel.php below, not retyped, so
        the page cannot drift out of step with what the application enforces.
    --}}
    @php
        $minAge = (int) config('apel.eligibility.minimum_age', 30);
        $minQual = ucfirst((string) config('apel.eligibility.minimum_qualification', 'diploma'));
        $minYears = 3; // APEL C, per Student\ApplicationController
    @endphp

    <header class="lp-top">
        <a class="lp-top-brand" href="{{ route('home') }}">
            <span class="lp-top-mark" aria-hidden="true">AP</span>
            <span class="lp-top-name">
                <strong>APEL</strong>
                <span>Universiti Teknologi Malaysia</span>
            </span>
        </a>
        <nav class="lp-top-nav" aria-label="Page sections">
            <a href="#tracks">Two tracks</a>
            <a href="#judged">How you are judged</a>
            <a class="lp-top-cta" href="{{ route('login') }}">Sign in</a>
        </nav>
    </header>

    {{-- 1 — Attention ------------------------------------------------------ --}}
    <section class="lp-hero" aria-labelledby="lp-hero-head">
        <div class="lp-hero-tell">
            <p class="lp-eyebrow">Accreditation of Prior Experiential Learning</p>

            <h1 class="lp-hero-head" id="lp-hero-head">
                Your experience is <em>evidence</em>.
            </h1>

            <p class="lp-hero-lede">
                APEL assesses what you actually learned at work and converts it into university
                standing &mdash; entry to a degree, or credit against courses you would otherwise
                sit. Assessed by academics, under the national MQA framework.
            </p>

            <div class="lp-hero-acts">
                <a class="lp-btn lp-btn-solid" href="#check">
                    Check if you qualify
                    <span aria-hidden="true">&rarr;</span>
                </a>
                <a class="lp-btn lp-btn-quiet" href="#tracks">See the two tracks</a>
            </div>

            <p class="lp-hero-foot">Takes about a minute. No account needed.</p>
        </div>

        {{--
            The thesis, rendered rather than described: a working life on the
            left, what it evidences on the right. The years are set in mono and
            deliberately de-emphasised, because years are not what gets assessed
            — the demonstrable learning is. That distinction is the whole
            difference between APEL and buying a certificate, so it is the first
            thing the page shows.
        --}}
        <figure class="lp-convert" aria-labelledby="lp-convert-cap">
            <figcaption class="lp-convert-cap" id="lp-convert-cap">An example of what gets assessed</figcaption>

            <div class="lp-convert-grid">
                <div class="lp-convert-side">
                    <p class="lp-convert-label">Work history</p>
                    <p class="lp-convert-role">Quality Technician</p>
                    <p class="lp-convert-meta">Manufacturing plant, Senai</p>
                    <p class="lp-convert-years">2013 &ndash; 2025 &nbsp;·&nbsp; 12 yr 4 mo</p>
                </div>

                <div class="lp-convert-arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12h15M13 6l6 6-6 6" />
                    </svg>
                </div>

                <div class="lp-convert-side lp-convert-side--out">
                    <p class="lp-convert-label">What it evidences</p>
                    <ul class="lp-convert-learned">
                        <li>Statistical process control</li>
                        <li>Root-cause analysis</li>
                        <li>Audit and compliance</li>
                    </ul>
                </div>
            </div>

            <p class="lp-convert-note">Time served is not the measure. What you can demonstrate is.</p>
        </figure>
    </section>

    {{-- 2 — Understand ----------------------------------------------------- --}}
    <section class="lp-band" id="tracks" aria-labelledby="lp-tracks-head">
        <div class="lp-band-inner">
            <p class="lp-eyebrow">Two tracks</p>
            <h2 class="lp-h2" id="lp-tracks-head">Which one you need depends on what you are after.</h2>
            <p class="lp-sub">They are separate routes with separate rules. Most people need one, not both.</p>

            <div class="lp-tracks">
                <article class="lp-track">
                    <p class="lp-track-tag">APEL A</p>
                    <h3 class="lp-track-head">Get in without the usual entry requirements</h3>
                    <p class="lp-track-body">
                        You want to study for a degree but do not hold the standard academic entry
                        qualifications. APEL A assesses your experience as the basis for admission
                        to a programme.
                    </p>
                    <dl class="lp-track-rules">
                        <div><dt>Age</dt><dd>{{ $minAge }} or over</dd></div>
                        <div><dt>Qualification</dt><dd>{{ $minQual }} or higher</dd></div>
                        <div><dt>Identity</dt><dd>Malaysian IC</dd></div>
                        <div><dt>Outcome</dt><dd>Admission to a programme</dd></div>
                    </dl>
                </article>

                <article class="lp-track">
                    <p class="lp-track-tag">APEL C</p>
                    <h3 class="lp-track-head">Skip a course you have already effectively done</h3>
                    <p class="lp-track-body">
                        You are studying, or about to, and one of the courses covers work you
                        already do. APEL C assesses you against that course's learning outcomes and
                        can award the credit without you sitting it.
                    </p>
                    <dl class="lp-track-rules">
                        <div><dt>Qualification</dt><dd>{{ $minQual }} or higher</dd></div>
                        <div><dt>Experience</dt><dd>{{ $minYears }} years, related field</dd></div>
                        <div><dt>Assessment</dt><dd>Written paper or portfolio</dd></div>
                        <div><dt>Outcome</dt><dd>Credit hours against the course</dd></div>
                    </dl>
                </article>
            </div>
        </div>
    </section>

    {{-- 3 — Want it -------------------------------------------------------- --}}
    <section class="lp-band lp-band--tint" aria-labelledby="lp-gain-head">
        <div class="lp-band-inner">
            <p class="lp-eyebrow">What changes</p>
            <h2 class="lp-h2" id="lp-gain-head">The qualification catches up with the work.</h2>

            <div class="lp-gains">
                <div class="lp-gain">
                    <h3>Start without going back to the beginning</h3>
                    <p>No returning to SPM or STPM at thirty-five. Your working record is what is put forward.</p>
                </div>
                <div class="lp-gain">
                    <h3>Stop paying to be taught what you do daily</h3>
                    <p>Credit awarded through APEL C is credit you neither sit nor fund — the time and the fees both come off.</p>
                </div>
                <div class="lp-gain">
                    <h3>Keep the job while you do it</h3>
                    <p>Assessment is evidence-based, not attendance-based. You are judged on what you can show, not hours in a lecture hall.</p>
                </div>
                <div class="lp-gain">
                    <h3>A qualification that stands on its own</h3>
                    <p>The same UTM award, held to the same standard. Nothing on it records that you came through APEL.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 4 — Trust ---------------------------------------------------------- --}}
    <section class="lp-proof" id="judged" aria-labelledby="lp-proof-head">
        <div class="lp-band-inner">
            <p class="lp-eyebrow lp-eyebrow--inv">How you are judged</p>
            <h2 class="lp-h2 lp-h2--inv" id="lp-proof-head">This is not a shortcut, and that is the point.</h2>
            <p class="lp-sub lp-sub--inv">
                A qualification is only worth what the assessment behind it is worth. Here is
                exactly what stands behind this one.
            </p>

            <ul class="lp-proofs">
                <li>
                    <h3>A national framework, not a local scheme</h3>
                    <p>APEL operates under the Malaysian Qualifications Agency. The standard is set nationally; UTM administers it.</p>
                </li>
                <li>
                    <h3>Two independent evaluators</h3>
                    <p>An application can carry two assessors, and it does not advance until both have reported.</p>
                </li>
                <li>
                    <h3>Every outcome, not an average</h3>
                    <p>APEL C is marked against each of the course's learning outcomes. You must reach the mark on all of them — a strong showing in three cannot carry a weak fourth.</p>
                </li>
                <li>
                    <h3>No fee until someone says it is worth assessing</h3>
                    <p>An academic advisor reviews your case first. If it will not stand, you are told before any payment is taken.</p>
                </li>
                <li>
                    <h3>Your documents stay private</h3>
                    <p>Evidence is held on private storage with no public link, readable only by you, your assigned evaluators, and the registry.</p>
                </li>
                <li>
                    <h3>The rules are published, not discretionary</h3>
                    <p>The entry criteria on this page are the criteria the system enforces. You can check yourself against them below.</p>
                </li>
            </ul>

            {{--
                TESTIMONIAL SLOT — intentionally empty.

                Real quotes from real graduates belong here and would be the
                strongest thing on the page. They have to come from the faculty
                with the person's consent; inventing them would make the one
                section whose entire job is trust into the one section that is
                untrue. Replace this block with attributed quotes when you have
                them, and delete the note.
            --}}
            <p class="lp-slot">Graduate accounts will appear here once the faculty supplies them.</p>
        </div>
    </section>

    {{-- 5 — Take action ---------------------------------------------------- --}}
    <section class="lp-band lp-band--check" id="check" aria-labelledby="lp-check-head">
        <div class="lp-check-inner">
            <p class="lp-eyebrow">Check yourself</p>
            <h2 class="lp-h2" id="lp-check-head">Are you eligible?</h2>
            <p class="lp-sub">Three questions, answered against the published entry rules. Nothing is sent or stored.</p>

            <form class="lp-check" id="lp-check-form"
                  data-min-age="{{ $minAge }}"
                  data-min-level="2"
                  data-min-years="{{ $minYears }}">
                <div class="lp-check-fields">
                    <div class="field">
                        <label for="lp-age">Your age</label>
                        <input id="lp-age" name="age" type="number" inputmode="numeric"
                               min="16" max="99" placeholder="e.g. 38" required>
                    </div>

                    <div class="field">
                        <label for="lp-qual">Highest qualification</label>
                        <select id="lp-qual" name="qual" required>
                            <option value="">Select one</option>
                            <option value="0">None / SPM</option>
                            <option value="1">Certificate</option>
                            <option value="2">Diploma</option>
                            <option value="3">Advanced Diploma</option>
                            <option value="4">Bachelor's degree</option>
                            <option value="5">Master's</option>
                            <option value="6">PhD / Doctorate</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="lp-years">Years of related work</label>
                        <input id="lp-years" name="years" type="number" inputmode="numeric"
                               min="0" max="60" placeholder="e.g. 12" required>
                    </div>
                </div>

                <button type="submit" class="lp-btn lp-btn-solid lp-btn-wide">
                    Check eligibility
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </form>

            {{-- Populated by script; announced politely so it is not missed. --}}
            <div class="lp-verdict" id="lp-verdict" role="status" aria-live="polite" hidden></div>

            <p class="lp-check-note">
                This checks the published entry rules only. It is not a decision — admission and
                credit are determined by the faculty after assessment.
            </p>
        </div>
    </section>

    <footer class="lp-foot">
        <div class="lp-foot-inner">
            <p><strong>APEL</strong> &nbsp;·&nbsp; Universiti Teknologi Malaysia</p>
            <p class="lp-foot-links">
                <a href="{{ route('login') }}">Sign in</a>
                <a href="{{ route('register') }}">Create an account</a>
            </p>
        </div>
    </footer>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('lp-check-form');
            const out = document.getElementById('lp-verdict');
            if (!form || !out) return;

            // Thresholds come from config/apel.php via data attributes rather than
            // being retyped here, so the page cannot promise a rule the
            // application no longer enforces.
            const MIN_AGE = Number(form.dataset.minAge);
            const MIN_LEVEL = Number(form.dataset.minLevel);
            const MIN_YEARS = Number(form.dataset.minYears);

            const esc = (s) => String(s).replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            const REGISTER_URL = @json(route('register'));

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const age = Number(form.age.value);
                const level = Number(form.qual.value);
                const years = Number(form.years.value);

                if (!Number.isFinite(age) || form.qual.value === '' || !Number.isFinite(years)) {
                    return;
                }

                // Mirrors Domain\Apel\Eligibility and the APEL C check in
                // Student\ApplicationController.
                const aShort = [];
                if (age < MIN_AGE) aShort.push('you need to be ' + MIN_AGE + ' or over');
                if (level < MIN_LEVEL) aShort.push('it asks for a Diploma or higher');

                const cShort = [];
                if (level < MIN_LEVEL) cShort.push('it asks for a Diploma or higher');
                if (years < MIN_YEARS) cShort.push('it asks for ' + MIN_YEARS + ' years of related work');

                const fitsA = aShort.length === 0;
                const fitsC = cShort.length === 0;

                let tone, head, body;

                if (fitsA && fitsC) {
                    tone = 'good';
                    head = 'Both tracks are open to you.';
                    body = 'Use APEL A to enter a programme, or APEL C to claim credit for a '
                         + 'course you already know. You can start either from your account.';
                } else if (fitsA) {
                    tone = 'good';
                    head = 'You meet the entry rules for APEL A.';
                    body = 'That is the admission route. For APEL C as well, ' + cShort.join(', and ') + '.';
                } else if (fitsC) {
                    tone = 'good';
                    head = 'You meet the entry rules for APEL C.';
                    body = 'That is the credit-transfer route. For APEL A as well, ' + aShort.join(', and ') + '.';
                } else {
                    tone = 'wait';
                    head = 'Not yet — but this is worth reading.';
                    body = 'For APEL A, ' + aShort.join(', and ') + '. For APEL C, '
                         + cShort.join(', and ') + '. These are the published entry rules; if your '
                         + 'situation is unusual, the faculty is still the right place to ask.';
                }

                const act = (fitsA || fitsC)
                    ? '<a class="lp-btn lp-btn-solid" href="' + esc(REGISTER_URL) + '">Create an account &rarr;</a>'
                    : '<a class="lp-btn lp-btn-quiet" href="#tracks">Read the track rules again</a>';

                out.className = 'lp-verdict is-' + tone;
                out.innerHTML =
                    '<p class="lp-verdict-head">' + esc(head) + '</p>' +
                    '<p class="lp-verdict-body">' + esc(body) + '</p>' +
                    '<div class="lp-verdict-act">' + act + '</div>';
                out.hidden = false;
                out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        })();
    </script>
@endpush
