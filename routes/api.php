<?php

use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Api\AudioController;
use App\Http\Controllers\Api\PasswordCodeController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\WeeklyChallengeController;
use App\Http\Controllers\Api\PlatformContentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushDeviceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('content', [PlatformContentController::class, 'show'])->middleware('throttle:120,1');
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
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{publication}/read', [NotificationController::class, 'markRead'])
            ->whereNumber('publication')->middleware('throttle:60,1');
        Route::delete('notifications/{publication}', [NotificationController::class, 'destroy'])
            ->whereNumber('publication')->middleware('throttle:60,1');
        Route::post('devices/push', [PushDeviceController::class, 'register'])
            ->middleware('throttle:15,1');
        Route::delete('devices/push', [PushDeviceController::class, 'unregister'])
            ->middleware('throttle:15,1');

        Route::get('community/posts', [CommunityController::class, 'index'])->name('community.posts.index');
        Route::post('community/posts', [CommunityController::class, 'store'])->middleware('throttle:10,1');
        Route::get('community/posts/{post}', [CommunityController::class, 'show'])->name('community.posts.show');
        Route::post('community/posts/{post}/replies', [CommunityController::class, 'reply'])->middleware('throttle:20,1');
        Route::delete('community/posts/{post}', [CommunityController::class, 'destroy'])->middleware('throttle:30,1');
        Route::delete('community/posts/{post}/replies/{reply}', [CommunityController::class, 'destroyReply'])
            ->middleware('throttle:30,1');

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
