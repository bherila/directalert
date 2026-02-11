<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\AdminImportController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route for the home page, showing the verification form
Route::get('/', [VerificationController::class, 'showVerificationForm']);

// Route for submitting the verification form
Route::post('/verify', [VerificationController::class, 'verifyAccount']);

// Route for showing the update information form
Route::get('/update-information', [VerificationController::class, 'showUpdateInformationForm']);

// Route for submitting the update information form
Route::post('/update-information', [VerificationController::class, 'updateInformation']);

// Route for the thank you page
Route::get('/thanks', [VerificationController::class, 'showThanksPage']);

// Admin routes with both auth and admin middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/export', [AdminExportController::class, 'index']);
    Route::get('/admin/import', [AdminImportController::class, 'index']);
    Route::post('/admin/import', [AdminImportController::class, 'import']);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Login Routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Registration Routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});
