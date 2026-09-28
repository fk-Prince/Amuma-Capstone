<?php

use App\Exceptions\ExternalServiceException;
use App\Http\Middleware\EnsurePortalClient;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
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
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getPrevious() || $e->getMessage() === ''
                    ? (Response::$statusTexts[$status] ?? 'Error')
                    : $e->getMessage();

                return response()->json(['message' => $message], $status);
            }

            if ($e instanceof ExternalServiceException) {
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }

            if ($e instanceof ConnectionException || $e instanceof RequestException) {
                $target = $e->getMessage() . ' ' . ($e instanceof RequestException
                    ? (string) $e->response->effectiveUri()
                    : '');

                return response()->json([
                    'message' => str_contains(strtolower($target), 'xendit')
                        ? ExternalServiceException::PAYMENT_FAILED
                        : ExternalServiceException::THIRD_PARTY_ERROR,
                ], 502);
            }

            if (get_class($e) === Exception::class) {
                $code = $e->getCode();

                return response()->json(
                    ['message' => $e->getMessage() ?: 'Internal Server Error'],
                    is_int($code) && $code >= 400 && $code < 600 ? $code : 500
                );
            }

            return response()->json(['message' => 'Internal Server Error'], 500);
        });
    })->create();
