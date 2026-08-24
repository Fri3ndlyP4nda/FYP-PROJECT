<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
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
        //
    })->create();
