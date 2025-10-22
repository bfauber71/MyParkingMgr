<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::middleware('readonly')->group(function () {
        Route::get('/vehicles/search', [VehicleController::class, 'search']);
        Route::get('/vehicles/export', [VehicleController::class, 'export']);
        Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::patch('/vehicles/{id}', [VehicleController::class, 'update']);
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
        
        Route::get('/properties', [PropertyController::class, 'index']);
    });
    
    Route::middleware('admin')->group(function () {
        Route::post('/properties', [PropertyController::class, 'store']);
        Route::patch('/properties/{id}', [PropertyController::class, 'update']);
        Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
        
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::patch('/admin/users/{id}', [UserController::class, 'update']);
        Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
    });
});
