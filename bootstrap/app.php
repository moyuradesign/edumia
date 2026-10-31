<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'active' => \App\Http\Middleware\EnsureActive::class,
        ]);
        $middleware->redirectGuestsTo('/login');
    })
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain-specific exception mapping belongs here.
    })
    ->create();
