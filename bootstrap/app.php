<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Deployed behind a platform load balancer (Render/Railway/etc.)
        // that terminates TLS and forwards plain HTTP internally - trust
        // its X-Forwarded-* headers so Laravel knows the original
        // request was HTTPS. Without this, generated asset/URL links
        // come back as http:// on an https:// page (mixed content the
        // browser silently blocks), leaving the Vue app unable to load
        // its own JS/CSS.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
