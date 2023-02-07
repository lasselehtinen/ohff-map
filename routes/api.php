<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::resource('users', UserController::class)->only(['show', 'update'])->middleware('auth:sanctum');
Route::resource('users', UserController::class)->only(['store']);
Route::put('/users/{user}/activations/{reference}', [UserController::class, 'userActivation'])->middleware('auth:sanctum');
Route::resource('references', ReferenceController::class)->only(['index', 'show'])->middleware('auth:sanctum');
Route::resource('programs', ProgramController::class)->only(['index', 'show']);
Route::post('login', [LoginController::class, 'authenticate'])->name('login');
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth:sanctum');
