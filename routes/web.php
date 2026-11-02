<?php

use App\Http\Controllers\Admin\CaseController as AdminCase;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Candidate\UploadController;
use App\Http\Controllers\Client\CaseController as ClientCase;
use App\Http\Controllers\Client\DashboardController as ClientDashboard;
use App\Http\Controllers\Client\ProfileController as ClientProfile;
use App\Http\Controllers\Client\ReportController as ClientReport;
use App\Http\Controllers\DocumentController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClientMiddleware;
use Illuminate\Support\Facades\Route;

// Root redirect (Route::redirect is route:cache-safe — no closure)
Route::redirect('/', '/login');

// ── Guest auth routes ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showClientLogin'])->name('login');
    Route::get('/admin/login', [LoginController::class, 'showAdminLogin'])->name('admin.login');
    Route::get('/forgot-password', [PasswordController::class, 'showForgot'])->name('password.request');
    Route::get('/reset-password/{token}', [PasswordController::class, 'showReset'])->name('password.reset');

    // Rate-limited POST routes
    Route::post('/login', [LoginController::class, 'clientLogin'])->name('login.post')->middleware('throttle:login');
    Route::post('/admin/login', [LoginController::class, 'adminLogin'])->name('admin.login.post')->middleware('throttle:login');
    Route::post('/forgot-password', [PasswordController::class, 'sendReset'])->name('password.email')->middleware('throttle:password-reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.update')->middleware('throttle:password-reset');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Force password reset (authenticated but not yet changed password)
Route::middleware('auth')->group(function () {
    Route::get('/set-password', [PasswordController::class, 'showSetPassword'])->name('password.set');
    Route::post('/set-password', [PasswordController::class, 'setPassword'])->name('password.set.post');
});

// ── Admin routes ──────────────────────────────────────────────────────────────
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    // Case management
    Route::get('/cases', [AdminCase::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [AdminCase::class, 'create'])->name('cases.create');
    Route::post('/cases', [AdminCase::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}', [AdminCase::class, 'show'])->name('cases.show');
    Route::get('/cases/{case}/report', [AdminCase::class, 'report'])->name('cases.report');
    Route::patch('/cases/{case}/checks/{check}', [AdminCase::class, 'updateCheck'])->name('cases.checks.update');
    Route::patch('/cases/{case}/status', [AdminCase::class, 'updateStatus'])->name('cases.status');
    Route::post('/cases/{case}/resend-link', [AdminCase::class, 'resendLink'])->name('cases.resend-link');
    Route::post('/cases/{case}/checks/{check}/insufficiency', [AdminCase::class, 'triggerInsufficiency'])->name('cases.checks.insufficiency');

    // User management
    Route::get('/users', [AdminUser::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUser::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUser::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [AdminUser::class, 'destroy'])->name('users.destroy');

    // MIS Reports
    Route::get('/reports/mis', [AdminReport::class, 'mis'])->name('reports.mis');
    Route::patch('/reports/cases/{case}/start-date', [AdminReport::class, 'updateCaseDate'])->name('reports.case.start-date');
});

// ── Client routes ─────────────────────────────────────────────────────────────
Route::middleware(['auth', ClientMiddleware::class])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientDashboard::class, 'index'])->name('dashboard');

    Route::get('/cases', [ClientCase::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [ClientCase::class, 'create'])->name('cases.create');
    Route::post('/cases', [ClientCase::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}', [ClientCase::class, 'show'])->name('cases.show');
    Route::get('/cases/{case}/report', [ClientCase::class, 'report'])->name('cases.report');

    // Company profile
    Route::get('/profile', [ClientProfile::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ClientProfile::class, 'update'])->name('profile.update');

    // MIS Report
    Route::get('/reports/mis', [ClientReport::class, 'mis'])->name('reports.mis');
});

// ── Document serving (auth required) ─────────────────────────────────────────
Route::get('/documents/{document}/view', [DocumentController::class, 'view'])
    ->name('documents.view')
    ->middleware('auth');

// ── Candidate public upload (token-gated, no auth) ───────────────────────────
Route::get('/upload/{token}', [UploadController::class, 'show'])->name('candidate.upload.show');
Route::post('/upload/{token}', [UploadController::class, 'store'])->name('candidate.upload.store')->middleware('throttle:candidate-upload');
