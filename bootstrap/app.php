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
    ->withMiddleware(function (Middleware $middleware): void {
        // Ngrok প্রক্সি থেকে আসা সকল HTTPS হেডার ট্রাস্ট করার জন্য
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            '/api/payment/success',
            '/api/payment/fail',
            '/api/payment/cancel',
            '/api/payment/ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();