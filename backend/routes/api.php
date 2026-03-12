<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HelpdeskController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // Events
        Route::apiResource('events', EventController::class);

        // Helpdesk
        Route::prefix('helpdesk')->group(function () {
            Route::get('chats/my', [HelpdeskController::class, 'myChats']);
            Route::post('chats', [HelpdeskController::class, 'startChat']);
            Route::post('chats/{chat}/messages', [HelpdeskController::class, 'sendMessage']);
            Route::post('chats/{chat}/transfer', [HelpdeskController::class, 'transfer']);
            Route::post('chats/{chat}/respond', [HelpdeskController::class, 'agentRespond']);
            Route::post('chats/{chat}/close', [HelpdeskController::class, 'closeChat']);
            Route::get('chats', [HelpdeskController::class, 'agentChats']);
            Route::get('chats/{chat}/messages', [HelpdeskController::class, 'getMessages']);
        });

    });

});
