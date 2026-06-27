<?php

use App\Http\Controllers\PublicChatController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ConfigController;
use App\Http\Controllers\Web\ConfigSuggestionController;
use App\Http\Controllers\Web\ConsoleController;
use App\Http\Controllers\Web\ConversationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DatasetController;
use App\Http\Controllers\Web\DocsController;
use App\Http\Controllers\Web\ExplorerController;
use App\Http\Controllers\Web\InsightsController;
use App\Http\Controllers\Web\GoogleController;
use App\Http\Controllers\Web\UploadController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'));

// --- Public embeddable widget (no auth; browser-facing) ---
Route::get('/embed.js', [WidgetController::class, 'embed']);
Route::options('/public/chat', [PublicChatController::class, 'preflight']);
Route::post('/public/chat', [PublicChatController::class, 'chat'])->middleware('throttle:60,1');
Route::options('/public/feedback', [PublicChatController::class, 'preflight']);
Route::post('/public/feedback', [PublicChatController::class, 'feedback'])->middleware('throttle:120,1');

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

    Route::post('/dashboard/datasets', [DatasetController::class, 'store']);
    Route::get('/dashboard/datasets/{dataset}', [DatasetController::class, 'show'])->name('dataset');
    Route::delete('/dashboard/datasets/{dataset}', [DatasetController::class, 'destroy']);
    Route::get('/dashboard/sources/{source}/status', [DatasetController::class, 'sourceStatus']);
    Route::get('/dashboard/datasets/{dataset}/explorer', [ExplorerController::class, 'show'])->name('explorer');
    Route::post('/dashboard/datasets/{dataset}/analyze', [ExplorerController::class, 'analyze']);

    Route::post('/dashboard/keys', [DashboardController::class, 'issueKey']);
    Route::post('/dashboard/keys/{apiKey}/revoke', [DashboardController::class, 'revokeKey']);
    Route::post('/dashboard/ask', [DashboardController::class, 'ask']);
    Route::post('/dashboard/upload', [UploadController::class, 'store']);

    Route::get('/dashboard/configs/create', [ConfigController::class, 'create']);
    Route::post('/dashboard/configs/suggest', [ConfigSuggestionController::class, 'generate']);
    Route::post('/dashboard/configs', [ConfigController::class, 'store']);
    Route::get('/dashboard/configs/{config}/edit', [ConfigController::class, 'edit']);
    Route::put('/dashboard/configs/{config}', [ConfigController::class, 'update']);
    Route::delete('/dashboard/configs/{config}', [ConfigController::class, 'destroy']);

    Route::get('/dashboard/console', [ConsoleController::class, 'index'])->name('console');
    Route::post('/dashboard/console/ask', [ConsoleController::class, 'ask']);

    Route::get('/dashboard/docs', [DocsController::class, 'index'])->name('docs');
    Route::get('/dashboard/insights', [InsightsController::class, 'index'])->name('insights');
    Route::get('/dashboard/conversations', [ConversationController::class, 'index'])->name('conversations');
    Route::get('/dashboard/conversations/{conversation}', [ConversationController::class, 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
