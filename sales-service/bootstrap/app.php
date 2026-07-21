<?php

use App\Exceptions\CatalogConflictException;
use App\Exceptions\CatalogUnavailableException;
use App\Http\Middleware\CorrelationIdMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            CorrelationIdMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => $e->validator->errors()->first() ?: 'Payload invalido.',
                'correlation_id' => $request->attributes->get('correlation_id'),
            ], 400);
        });

        $exceptions->render(function (CatalogConflictException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 'ESTOQUE_INSUFICIENTE',
                'message' => $e->getMessage(),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ], 409);
        });

        $exceptions->render(function (CatalogUnavailableException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 'CATALOGO_INDISPONIVEL',
                'message' => $e->getMessage(),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ], 503);
        });
    })->create();
