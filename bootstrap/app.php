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
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'track.visit' => \App\Http\Middleware\TrackSiteVisit::class,
        ]);

        // Chưa đăng nhập mà vào trang cần auth -> đưa về trang /auth (đăng
        // nhập/đăng ký chung 1 trang). Đã đăng nhập mà vào lại /auth (middleware
        // 'guest') -> đưa về trang chủ (admin thì về thẳng dashboard admin).
        $middleware->redirectGuestsTo(fn () => route('agri.auth'));
        $middleware->redirectUsersTo(
            fn ($request) => $request->user()?->is_admin ? route('admin.dashboard') : route('agri.index')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
