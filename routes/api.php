<?php

use App\Http\Controllers\GeoJsonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::get('/geojson', [GeoJsonController::class, 'index']);
