<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ConfigController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GoogleController;
use App\Http\Controllers\Web\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/keys', [DashboardController::class, 'issueKey']);
    Route::post('/dashboard/keys/{apiKey}/revoke', [DashboardController::class, 'revokeKey']);
    Route::post('/dashboard/ask', [DashboardController::class, 'ask']);
    Route::post('/dashboard/upload', [UploadController::class, 'store']);

    Route::get('/dashboard/configs/create', [ConfigController::class, 'create']);
    Route::post('/dashboard/configs', [ConfigController::class, 'store']);
    Route::get('/dashboard/configs/{config}/edit', [ConfigController::class, 'edit']);
    Route::put('/dashboard/configs/{config}', [ConfigController::class, 'update']);
    Route::delete('/dashboard/configs/{config}', [ConfigController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
