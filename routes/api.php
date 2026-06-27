<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\Api\QueryController;
use Illuminate\Support\Facades\Route;

// All routes here are prefixed with /v1 (see bootstrap/app.php apiPrefix)
// and authenticated by a tenant API key (Authorization: Bearer <key> or X-Api-Key).
Route::middleware('apikey')->group(function () {
    Route::post('/ingest', [IngestController::class, 'store']);
    Route::post('/query', [QueryController::class, 'store']);
    Route::get('/agents', [AgentController::class, 'index']);
});
