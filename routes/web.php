<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AudioController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\CommunityModerationController;
use App\Http\Controllers\Admin\PlatformContentController;
use App\Http\Controllers\Admin\WeeklyChallengeController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminAuthController::class, 'show'])->name('login');
Route::post('/', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('admin.login');
Route::get('/admin/login', fn () => redirect()->route('login'));
Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/content', [PlatformContentController::class, 'edit'])->name('content.edit');
    Route::patch('/content', [PlatformContentController::class, 'update'])->name('content.update');
    Route::prefix('audio/{category}')->where(['category' => 'podcasts|meditations|prayers'])->name('audio.')->group(function (): void {
        Route::get('/', [AudioController::class, 'index'])->name('index');
        Route::get('/create', [AudioController::class, 'create'])->name('create');
        Route::post('/', [AudioController::class, 'store'])->name('store');
        Route::get('/{audio}/edit', [AudioController::class, 'edit'])->name('edit');
        Route::patch('/{audio}', [AudioController::class, 'update'])->name('update');
        Route::delete('/{audio}', [AudioController::class, 'destroy'])->name('destroy');
    });
    Route::get('/challenges', [WeeklyChallengeController::class, 'index'])->name('challenges.index');
    Route::get('/challenges/create', [WeeklyChallengeController::class, 'create'])->name('challenges.create');
    Route::post('/challenges', [WeeklyChallengeController::class, 'store'])->name('challenges.store');
    Route::get('/challenges/{challenge}/edit', [WeeklyChallengeController::class, 'edit'])->name('challenges.edit');
    Route::patch('/challenges/{challenge}', [WeeklyChallengeController::class, 'update'])->name('challenges.update');
    Route::get('/community', [CommunityModerationController::class, 'index'])->name('community.index');
    Route::get('/community/{post}', [CommunityModerationController::class, 'show'])->name('community.show');
    Route::delete('/community/{post}', [CommunityModerationController::class, 'destroy'])->name('community.destroy');
    Route::delete('/community/{post}/replies/{reply}', [CommunityModerationController::class, 'destroyReply'])
        ->name('community.replies.destroy');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::patch('/members/{member}/status', [MemberController::class, 'toggleStatus'])->name('members.status');
});
