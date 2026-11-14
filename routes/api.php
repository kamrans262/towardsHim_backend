<?php

use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Api\PasswordCodeController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('auth/register', [MemberAuthController::class, 'register']);
        Route::post('auth/login', [MemberAuthController::class, 'login']);
        Route::post('auth/google', [SocialAuthController::class, 'google']);
        Route::post('auth/apple', [SocialAuthController::class, 'apple']);
        Route::post('auth/password/send-code', [PasswordCodeController::class, 'send']);
        Route::post('auth/password/verify-code', [PasswordCodeController::class, 'verify']);
        Route::post('auth/password/reset', [PasswordCodeController::class, 'reset']);
    });

    Route::middleware('api.member')->group(function (): void {
        Route::get('auth/me', [MemberAuthController::class, 'me']);
        Route::patch('auth/profile', [MemberAuthController::class, 'updateProfile']);
        Route::post('auth/avatar', [MemberAuthController::class, 'updateAvatar']);
        Route::post('auth/logout', [MemberAuthController::class, 'logout']);
        Route::post('auth/logout-all', [MemberAuthController::class, 'logoutEverywhere']);
        Route::delete('auth/account', [MemberAuthController::class, 'deleteAccount']);
    });
});
