<?php

use App\Domain\Apel\ConcurrentStageChange;
use App\Domain\Apel\IllegalStageTransition;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        /*
         * Applied to every web response. These are instructions to the browser
         * - a content security policy, no framing, no MIME sniffing - and the
         * browser is the only thing that can enforce them, so nothing the
         * server checks substitutes for them.
         */
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
        /**
         * Previously trustProxies(at: '*'), which trusts every client as a proxy
         * and therefore honours a client-supplied X-Forwarded-Host. Because url()
         * derives its root from the request, an attacker could POST
         * /forgot-password with a forged host header and have the victim receive
         * a genuine email from this system carrying a valid reset token pointed
         * at the attacker's domain. It also made $request->ip() - written into
         * every ActivityLog row - attacker-controlled, so the audit trail could
         * not attribute any action.
         *
         * Trust only private ranges, where a real reverse proxy would sit. If
         * this is deployed behind a proxy on a known address, list it explicitly.
         * Set APP_URL so generated links come from config, not the request.
         */
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Workflow refusals become a message on the page the person is already
         * looking at, not a 500.
         *
         * StageMachine::transition() is called from twenty-one places and only
         * two of them caught anything. Handling it here rather than at each
         * call site means a new one cannot forget: the default is a sentence
         * the reader can act on, and a controller that wants something more
         * specific can still catch it itself.
         */
        $exceptions->render(function (ConcurrentStageChange $e, Request $request) {
            $message = $e->forHumans();

            return $request->expectsJson()
                ? response()->json(['message' => $message], 409)
                : back()->withErrors(['stage' => $message])->withInput();
        });

        $exceptions->render(function (IllegalStageTransition $e, Request $request) {
            $message = $e->forHumans();

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['stage' => $message])->withInput();
        });
    })->create();
