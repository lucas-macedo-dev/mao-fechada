<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\ResolveApiLocale;
use App\Services\ActivityLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', [
            ResolveApiLocale::class,
        ]);
        $middleware->alias(['verified' => EnsureEmailIsVerified::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Throwable $exception, Request $request): ?JsonResponse {
            if (!$request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'type' => 'validation_error',
                        'message' => __('messages.validation_failed'),
                        'details' => $exception->errors(),
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'error' => [
                        'type' => 'authentication_error',
                        'message' => __('messages.unauthenticated'),
                    ],
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'error' => [
                        'type' => 'authorization_error',
                        'message' => $exception->getMessage() ?: __('messages.forbidden'),
                    ],
                ], Response::HTTP_FORBIDDEN);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'error' => [
                        'type' => 'not_found',
                        'message' => __('messages.resource_not_found'),
                    ],
                ], Response::HTTP_NOT_FOUND);
            }

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
                $fallbackMessage = Response::$statusTexts[$statusCode] ?? 'HTTP error.';

                if ($statusCode >= 500) {
                    app(ActivityLogger::class)->log('system.exception', auth()->id(), [
                        'exception_class' => get_class($exception),
                        'message' => mb_substr($exception->getMessage(), 0, 2000),
                        'path' => $request->path(),
                    ]);
                }

                return response()->json([
                    'error' => [
                        'type' => $statusCode === Response::HTTP_FORBIDDEN ? 'authorization_error' : 'http_error',
                        'message' => $exception->getMessage() ?: $fallbackMessage ?: __('messages.http_error'),
                    ],
                ], $statusCode);
            }

            app(ActivityLogger::class)->log('system.exception', auth()->id(), [
                'exception_class' => get_class($exception),
                'message' => mb_substr($exception->getMessage(), 0, 2000),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => [
                    'type' => 'server_error',
                    'message' => config('app.debug') ? $exception->getMessage() : __('messages.server_error'),
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
