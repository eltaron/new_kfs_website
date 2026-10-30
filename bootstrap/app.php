<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/efinance/callback', // استثناء رابط شركة e-finance من فحص التوكن
            '/efinance/gis/callback', // رابط قديم يخدمه نفس المعالج الموحد
            '/payments/return/*', // عودة المتصفح من بوابة الدفع (POST عبر المواقع)
        ]);

        // Security hardening: headers on every response, 2FA session gate on web routes.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureTwoFactorIsVerified::class);
        $middleware->alias(['two-factor' => \App\Http\Middleware\EnsureTwoFactorIsVerified::class]);

        // Trusted proxies come from config('security.trusted_proxies') (env TRUSTED_PROXIES).
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\TrustProxies::class
        );
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
