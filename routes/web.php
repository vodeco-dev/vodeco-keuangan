<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Menggunakan middleware 'auth' untuk memastikan hanya user yang sudah login
// yang bisa mengakses halaman-halaman ini.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/debts', [DebtController::class, 'index'])->name('debts');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
});

require __DIR__ . '/auth.php';