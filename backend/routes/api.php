<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EventAttendeeController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HelpdeskController;
use App\Http\Controllers\Api\V1\MfaController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{event}', [EventController::class, 'show']);

    // Public auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
        Route::post('login-mfa', [AuthController::class, 'loginWithMfa']);
        Route::post('register', [AuthController::class, 'register']);
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // Events
        Route::post('events', [EventController::class, 'store']);
        Route::put('events/{event}', [EventController::class, 'update']);
        Route::delete('events/{event}', [EventController::class, 'destroy']);
        Route::get('events/joined', [EventAttendeeController::class, 'joinedEvents']);
        Route::post('events/{event}/join', [EventAttendeeController::class, 'join']);
        Route::delete('events/{event}/leave', [EventAttendeeController::class, 'leave']);

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

        // MFA
        Route::prefix('mfa')->group(function () {
            Route::get('setup', [MfaController::class, 'setup']);
            Route::post('enable', [MfaController::class, 'enable']);
            Route::post('disable', [MfaController::class, 'disable']);
            Route::post('verify', [MfaController::class, 'verify']);
        });
    });
});
