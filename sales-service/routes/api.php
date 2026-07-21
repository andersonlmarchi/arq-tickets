<?php

use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/eventos', [EventoController::class, 'index']);
Route::post('/compras/iniciar', [CompraController::class, 'iniciar']);
Route::post('/compras/confirmar', [CompraController::class, 'confirmar']);
Route::post('/compras/cancelar', [CompraController::class, 'cancelar']);
