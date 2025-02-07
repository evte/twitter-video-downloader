<?php

use App\Http\Controllers\TwitterVideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('twitter-video');
});