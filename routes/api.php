<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Публичные роуты (вход и регистрация)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Защищенные роуты (нужен Bearer токен)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // Доступно всем авторизованным (включая guest)
    Route::get('/hazards', [HazardPointController::class, 'index']);
    Route::post('/hazards/check', [HazardPointController::class, 'checkProximity']);

    // Работник, модератор и админ могут отправлять новые точки
    Route::middleware('role:worker,moderator,admin')->group(function () {
        Route::post('/hazards', [HazardPointController::class, 'store']);
    });

    // Модератор и админ
    Route::middleware('role:moderator,admin')->group(function () {
        Route::get('/moderation/hazards', [HazardPointController::class, 'pending']);
        Route::patch('/hazards/{hazardPoint}', [HazardPointController::class, 'update']);
        Route::delete('/hazards/{hazardPoint}', [HazardPointController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{user}/approve-worker', [UserController::class, 'promoteToWorker']);
    });

    // Только админ
    Route::middleware('role:admin')->group(function () {
        Route::patch('/users/{user}/role', [UserController::class, 'changeRole']);
    });
});
