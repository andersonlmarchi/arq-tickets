<?php

use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/openapi.json', function () {
    return response()->file(
        resource_path('openapi/api.json'),
        ['Content-Type' => 'application/json'],
    );
});
Route::get('/eventos', [EventoController::class, 'index']);
Route::post('/compras/iniciar', [CompraController::class, 'iniciar']);
Route::post('/compras/confirmar', [CompraController::class, 'confirmar']);
Route::post('/compras/cancelar', [CompraController::class, 'cancelar']);
