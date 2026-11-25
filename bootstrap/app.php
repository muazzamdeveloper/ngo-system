<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        $middleware->alias([
            'account.active' => EnsureAccountActive::class,
            'admin.access' => EnsureAdminAccess::class,
            'password.changed' => EnsurePasswordChanged::class,
            'super.admin' => EnsureSuperAdmin::class,
        ]);

        // Where an already logged-in visitor is sent if they open the login page.
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->hasPermission('admin.access')
            ? route('dashboard')
            : route('account.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The Aadhaar number is never copied into the session after a failed form.
        $exceptions->dontFlash(['aadhaar']);

        // Files together are bigger than the server accepts (PHP post_max_size).
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('apply')) {
                return redirect()->route('apply.create')->withErrors(['form' => 'The files are too large to send together. Please use smaller images (each under 5 MB, photo under 2 MB).']);
            }
        });
    })->create();
