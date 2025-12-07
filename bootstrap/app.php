<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth:sanctum' => EnsureFrontendRequestsAreStateful::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'auth' => App\Http\Middleware\Authenticate::class,
            'payroll.auth' => App\Http\Middleware\CheckPayrollApiToken::class,
            'auth.custom' => App\Http\Middleware\AuthenticateWithCustomToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport(AuthenticationException::class);
        $exceptions->report(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'Unauthorized access. Please log in.',
            ], 401);
        });
    })->create();
