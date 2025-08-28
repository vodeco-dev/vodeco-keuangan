<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/transactions', [TransactionsController::class, 'index'])->name('transactions');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories');

require __DIR__ . '/auth.php';