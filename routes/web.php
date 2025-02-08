<?php

use App\Http\Controllers\TwitterVideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('twitter-video');
});
Route::get('/twitter', function () {
    return view('twitter-video');
});

Route::prefix('api/twitter')->group(function () {
    Route::post('/extract', [TwitterVideoController::class, 'extract']);
    Route::post('/download', [TwitterVideoController::class, 'download']);
    Route::get('/status/{id}', [TwitterVideoController::class, 'status']);
});

Route::get('/terms', function () {
    return view('terms');
});

Route::get('/privacy', function () {
    return view('privacy');
});

