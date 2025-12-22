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
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclure le webhook WhatsApp de la vérification CSRF
        $middleware->validateCsrfTokens(except: [
            '/webhook',
            '/webhook/*',
        ]);

        // Rediriger les utilisateurs non authentifiés vers la page de connexion
        $middleware->redirectTo(
            guests: '/login',
            users: '/admin'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
