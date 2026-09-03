{{--
    The anti-automation control, as one drop-in block.

    Replaces the arithmetic captcha. Nothing here asks the person to do
    anything: the browser solves a small hash puzzle while they are typing, and
    the whole thing is usually finished before they reach the password field.

    Three parts, and they are separate on purpose:

      - the proof of work, which costs an attacker CPU per attempt
      - a honeypot, hidden from people and from screen readers, that a
        form-filling script will populate
      - the render time, so a submission that arrives faster than a person
        could physically type is refused

    A person with JavaScript disabled cannot solve the puzzle. That is stated
    plainly below rather than left as a silent failure, because a candidate who
    cannot sign in and is not told why has no way forward - and the alternative,
    letting the form through when the script did not run, would mean the control
    could be skipped by turning JavaScript off.
--}}
@php
    $challenge = \App\Domain\Security\ProofOfWork::issue();
    $honeypot = \App\Domain\Security\HumanSignals::HONEYPOT;
    $openedAt = \App\Domain\Security\HumanSignals::RENDERED_AT;
@endphp

<div class="human-check" data-human-check>
    {{-- Signed challenge. Any edit to these invalidates the signature. --}}
    <input type="hidden" name="pow_salt" value="{{ $challenge['salt'] }}" data-pow-salt>
    <input type="hidden" name="pow_target" value="{{ $challenge['target'] }}" data-pow-target>
    <input type="hidden" name="pow_difficulty" value="{{ $challenge['difficulty'] }}" data-pow-difficulty>
    <input type="hidden" name="pow_expires" value="{{ $challenge['expires'] }}">
    <input type="hidden" name="pow_signature" value="{{ $challenge['signature'] }}">
    <input type="hidden" name="pow_answer" value="" data-pow-answer>

    <input type="hidden" name="{{ $openedAt }}" value="{{ time() }}">

    {{--
        Hidden from sight and from assistive technology, never from the DOM -
        a script reading the markup has no way to tell it is a trap.
        autocomplete="off" and tabindex="-1" keep a browser's form-filler and a
        keyboard user out of it.
    --}}
    <div class="honeypot" aria-hidden="true">
        <label for="{{ $honeypot }}">Leave this field empty</label>
        <input type="text" id="{{ $honeypot }}" name="{{ $honeypot }}"
               tabindex="-1" autocomplete="off" value="">
    </div>

    <p class="human-check-state" data-pow-state role="status" aria-live="polite">
        <span class="human-check-dot" aria-hidden="true"></span>
        <span data-pow-text>Checking your browser&hellip;</span>
    </p>

    <noscript>
        <p class="notice notice--bad">
            This form needs JavaScript to complete its security check. Please enable it and
            reload the page.
        </p>
    </noscript>

    <x-field-error name="pow_answer" />
</div>

@once
    @push('scripts')
        <script>
            /*
             * Solve the challenge: find n where sha256(salt + n) matches the
             * target. There is no shortcut through a hash, so the browser has
             * to actually search - which is the cost being imposed.
             *
             * Runs on an idle callback so it never competes with the page
             * painting, and yields every few thousand attempts so the tab stays
             * responsive on a slow phone.
             */
            (function () {
                const blocks = document.querySelectorAll('[data-human-check]');
                if (!blocks.length || !window.crypto || !window.crypto.subtle) {
                    blocks.forEach(function (b) {
                        const t = b.querySelector('[data-pow-text]');
                        if (t) t.textContent = 'This browser cannot complete the security check.';
                    });
                    return;
                }

                const encoder = new TextEncoder();

                /*
                 * Yield through a MessageChannel, not setTimeout.
                 *
                 * A hidden tab throttles setTimeout to roughly half a second -
                 * measured at 483ms here - so a solver that yielded every 2000
                 * iterations took over half a minute in a background tab, and a
                 * candidate who opened the sign-in page in one came back to a
                 * form that would not submit. A MessageChannel message is a
                 * macrotask that browsers do not throttle that way, so the page
                 * stays responsive and the work finishes at the same speed
                 * whether the tab is in front or not.
                 */
                function yieldToBrowser() {
                    return new Promise(function (resolve) {
                        const channel = new MessageChannel();
                        channel.port1.onmessage = function () {
                            channel.port1.close();
                            resolve();
                        };
                        channel.port2.postMessage(null);
                    });
                }

                async function sha256(text) {
                    const digest = await crypto.subtle.digest('SHA-256', encoder.encode(text));
                    return [...new Uint8Array(digest)]
                        .map(b => b.toString(16).padStart(2, '0'))
                        .join('');
                }

                async function solve(block) {
                    const salt = block.querySelector('[data-pow-salt]').value;
                    const target = block.querySelector('[data-pow-target]').value;
                    const max = Number(block.querySelector('[data-pow-difficulty]').value);
                    const answerField = block.querySelector('[data-pow-answer]');
                    const state = block.querySelector('[data-pow-state]');
                    const text = block.querySelector('[data-pow-text]');

                    // Yield on elapsed time rather than a fixed iteration count,
                    // so a fast machine barely pauses and a slow one still never
                    // blocks the page for more than a frame or two.
                    let lastYield = performance.now();

                    for (let n = 0; n <= max; n++) {
                        if (await sha256(salt + n) === target) {
                            answerField.value = String(n);
                            state.classList.add('is-done');
                            text.textContent = 'Security check complete.';
                            return;
                        }

                        if (performance.now() - lastYield > 40) {
                            await yieldToBrowser();
                            lastYield = performance.now();
                        }
                    }

                    text.textContent = 'The security check could not be completed. Please reload.';
                }

                blocks.forEach(function (block) {
                    // Started on an idle callback where one exists, but with a
                    // short timeout so a busy or hidden tab still begins
                    // promptly - the person may be typing already.
                    if ('requestIdleCallback' in window) {
                        requestIdleCallback(function () { solve(block); }, { timeout: 300 });
                    } else {
                        setTimeout(function () { solve(block); }, 0);
                    }
                });
            })();
        </script>
    @endpush
@endonce
