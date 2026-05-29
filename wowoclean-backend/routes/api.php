<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContainerController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('gateway/containers')->group(function () {
            Route::get('/', [ContainerController::class, 'index'])->middleware('role:admin,user');
            Route::get('/search', [ContainerController::class, 'index'])->middleware('role:admin,user');
            Route::get('/{id}/logs', [ContainerController::class, 'logs'])->middleware('role:admin,user');
            
            Route::post('/', [ContainerController::class, 'store'])->middleware('role:admin');
            Route::patch('/{id}/archive', [ContainerController::class, 'archive'])->middleware('role:admin');
            Route::delete('/{id}', [ContainerController::class, 'destroy'])->middleware('role:admin');
        });
    });
});