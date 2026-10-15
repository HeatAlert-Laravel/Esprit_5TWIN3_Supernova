<?php

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
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        // Signed-in users who open a guest page (login, register...) go to their own home.
        $middleware->redirectUsersTo(fn (Request $request): string => $request->user()?->role === 'ADMIN'
            ? route('admin.dashboard')
            : route('my-profile'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
