<?php

use App\Http\Middleware\EnsurePortalClient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    // ->withRouting(
    //     web: __DIR__ . '/../routes/web.php',
    //     api: __DIR__ . '/../routes/api.php',
    //     commands: __DIR__ . '/../routes/console.php',
    //     health: '/up',
    //     then: function () {
    //         require __DIR__ . '/../routes/channels.php';
    //     },
    // )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->alias([
            'portal' => EnsurePortalClient::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e) {
            $code = $e->getCode();

            $status = match (true) {
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                is_int($code) && $code >= 400 && $code < 600 => $code,
                default => 500,
            };

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], $status);
        });
    })->create();
