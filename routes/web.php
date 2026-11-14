<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminAuthController::class, 'show'])->name('login');
Route::post('/', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('admin.login');
Route::get('/admin/login', fn () => redirect()->route('login'));
Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::patch('/members/{member}/status', [MemberController::class, 'toggleStatus'])->name('members.status');
});
