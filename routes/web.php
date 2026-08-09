<?php

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ThresholdController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/history', HistoryController::class)->name('history');
    Route::get('/history/data', [HistoryController::class, 'data'])->name('history.data');
    Route::get('/history/chart', [HistoryController::class, 'chart'])->name('history.chart');
    Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');
    Route::get('/history/export/excel', [ReportExportController::class, 'excel'])->name('history.export.excel');
    Route::get('/history/report', [ReportExportController::class, 'report'])->name('history.report');
    Route::get('/api-docs', ApiDocumentationController::class)->name('api-docs');
    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
    Route::get('/settings', SettingController::class)->name('settings');
    Route::put('/settings/device', [SettingController::class, 'updateDevice'])->name('settings.device.update');
    Route::post('/settings/telegram/test', [SettingController::class, 'testTelegram'])->name('settings.telegram.test');
    Route::post('/settings/telegram/toggle', [SettingController::class, 'toggleTelegram'])->name('settings.telegram.toggle');
    Route::put('/thresholds', [ThresholdController::class, 'update'])->name('thresholds.update');
});
