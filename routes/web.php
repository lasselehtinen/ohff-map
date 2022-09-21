<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\GeoJsonController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    return view('welcome', ['request' => $request]);
});

Route::get('/geojson', [GeoJsonController::class, 'index']);

Route::get('suggest', [ReferenceController::class, 'create']);
Route::post('store-reference', [ReferenceController::class, 'store']);

require __DIR__.'/auth.php';
