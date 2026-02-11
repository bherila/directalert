<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DirectAlertDumpController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Add the web middleware group to ensure session handling works properly for browser-based form submissions
Route::middleware(['web', 'auth', 'admin'])->post('/admin/export/csv', [DirectAlertDumpController::class, 'dumpCsv']);
