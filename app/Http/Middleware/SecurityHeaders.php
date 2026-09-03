<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response headers that tell the browser to enforce things the server cannot.
 *
 * The application had none of these. Every control it did have - ownership
 * checks, rate limits, hashed codes - runs on the server, which means none of
 * them help once something is running inside the candidate's own page. These
 * headers close that gap: they are instructions to the browser, and the browser
 * is the only thing that can act on them.
 *
 * The two that matter most here:
 *
 *   Content-Security-Policy stops injected script from executing at all. This
 *   application renders applicant-supplied text - names, employers, job
 *   descriptions, appeal statements - on staff screens. Blade escapes it, so
 *   there is no known injection today; CSP is what limits the damage if one is
 *   ever introduced, which is the point of a control that costs nothing.
 *
 *   X-Frame-Options stops the site being framed. Without it an attacker's page
 *   can load a real, logged-in admin screen invisibly and trick the officer
 *   into clicking a button on it - approving an application, deleting an
 *   account - because the click lands on the real page underneath.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /*
         * 'unsafe-inline' for scripts and styles is a real weakening and is
         * here honestly rather than silently: several views carry inline
         * <script> blocks and the spine reads an inline custom property, so a
         * strict policy would break the interface today. Removing it means
         * moving those to nonces, which is worth doing and is not a change to
         * make quietly alongside a header.
         *
         * Everything else is locked to this origin. Fonts come from Google, so
         * those two hosts are named; no other external source can load.
         */
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data:",
            "connect-src 'self'",
            // No plugins, and nothing may frame this site.
            "object-src 'none'",
            "frame-ancestors 'none'",
            // A stolen <base> tag can silently repoint every relative URL.
            "base-uri 'self'",
            // Forms may only post back here, so a hijacked action cannot
            // exfiltrate a password to another origin.
            "form-action 'self'",
        ]);

        $headers = [
            'Content-Security-Policy' => $csp,

            // Older browsers that ignore frame-ancestors still honour this.
            'X-Frame-Options' => 'DENY',

            // Stops a browser guessing that an uploaded .pdf is really HTML and
            // running it. Documents here are applicant-supplied.
            'X-Content-Type-Options' => 'nosniff',

            // Do not leak the path of an internal page - which can carry an
            // application id - to an external site the user clicks through to.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Nothing here needs a camera, microphone or location.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ];

        /*
         * HSTS only over HTTPS. Sending it over plain HTTP is meaningless, and
         * setting it during local development would pin the browser to https
         * for 127.0.0.1 and break every other project on that host.
         */
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            // Never overwrite a header a controller set deliberately -
            // SecureFileController sets its own Cache-Control and nosniff for
            // document downloads.
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
