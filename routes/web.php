<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringRevenueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceCostController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Menggunakan middleware 'auth' untuk memastikan hanya user yang sudah login
// yang bisa mengakses halaman-halaman ini.
Route::middleware(['auth', 'role:admin,accountant,staff'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Bawaan Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource Controllers (CRUD)
    Route::resource('transactions', TransactionController::class)->except(['edit', 'show']);
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::resource('service_costs', ServiceCostController::class)->except(['show']);
    Route::resource('debts', DebtController::class);
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store']);
    Route::resource('recurring_revenues', RecurringRevenueController::class)->except(['create', 'edit', 'show']);

    // Route untuk Aksi Spesifik
    // Invoicing
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'markPaid'])->name('invoices.pay');
    // Recurring Revenues
    Route::patch('recurring_revenues/{recurring_revenue}/pause', [RecurringRevenueController::class, 'pause'])->name('recurring_revenues.pause');
    Route::patch('recurring_revenues/{recurring_revenue}/resume', [RecurringRevenueController::class, 'resume'])->name('recurring_revenues.resume');
    // Debts
    Route::post('debts/{debt}/pay', [DebtController::class, 'storePayment'])->name('debts.pay');

    // Laporan
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Pengaturan
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});

// Route Khusus untuk Admin - Manajemen User
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    // Anda bisa menambahkan route untuk edit dan delete di sini nanti
});

require __DIR__.'/auth.php';
