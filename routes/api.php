<?php

use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Api\AudioController;
use App\Http\Controllers\Api\PasswordCodeController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\WeeklyChallengeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('audio', [AudioController::class, 'index'])->middleware('throttle:120,1');
    Route::get('audio/{audio}', [AudioController::class, 'show'])->middleware('throttle:120,1');
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
        Route::get('challenges', [WeeklyChallengeController::class, 'index'])->name('challenges.index');
        Route::get('challenges/{challenge}', [WeeklyChallengeController::class, 'show'])->name('challenges.show');
        Route::put('challenges/{challenge}/days/{day}', [WeeklyChallengeController::class, 'setCompletion'])
            ->whereNumber('day')->middleware('throttle:60,1')->name('challenges.days.set');
        Route::get('auth/me', [MemberAuthController::class, 'me']);
        Route::patch('auth/profile', [MemberAuthController::class, 'updateProfile']);
        Route::post('auth/avatar', [MemberAuthController::class, 'updateAvatar']);
        Route::post('auth/logout', [MemberAuthController::class, 'logout']);
        Route::post('auth/logout-all', [MemberAuthController::class, 'logoutEverywhere']);
        Route::delete('auth/account', [MemberAuthController::class, 'deleteAccount']);
        Route::post('auth/account/revoke-social', [SocialAuthController::class, 'deleteAccount'])->middleware('throttle:6,1');
    });
});
