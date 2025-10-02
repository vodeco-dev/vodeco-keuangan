<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;


use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\DeletionApprovalController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\UserDeletionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoices/public/create', [InvoiceController::class, 'createPublic'])->name('invoices.public.create');
Route::post('/invoices/public', [InvoiceController::class, 'storePublic'])->name('invoices.public.store');
Route::get('/invoices/public/check-status', [InvoiceController::class, 'checkStatus'])->name('invoices.public.check-status');

// Route untuk melihat invoice secara publik tanpa perlu login
Route::get('/invoices/view/{token}', [InvoiceController::class, 'showPublic'])->name('invoices.public.show');

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
    Route::get('/transactions/proof/{transaction:proof_token}', [TransactionController::class, 'showProof'])
        ->name('transactions.proof.show');
    Route::resource('transactions', TransactionController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::post('debts/category-preferences', [DebtController::class, 'updateCategoryPreferences'])->name('debts.category-preferences.update');
    Route::resource('debts', DebtController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::get('customer-services/create', [CustomerServiceController::class, 'create'])->name('customer-services.create');
    Route::post('customer-services', [CustomerServiceController::class, 'store'])->name('customer-services.store');

    // Route untuk Aksi Spesifik
    // Invoicing
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'storePayment'])->name('invoices.pay');
    // Debts
    Route::post('debts/{debt}/pay', [DebtController::class, 'storePayment'])->name('debts.pay');
    Route::post('debts/{debt}/fail', [DebtController::class, 'markAsFailed'])->name('debts.fail');

    // Laporan
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('role:admin,accountant')
        ->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('role:admin,accountant')
        ->name('reports.export');

    // Pengaturan
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/display', [SettingController::class, 'display'])->name('settings.display');
    Route::post('/settings/display', [SettingController::class, 'updateDisplay'])->name('settings.display.update');
    Route::get('/settings/notifications', [SettingController::class, 'notifications'])->name('settings.notifications');
    Route::post('/settings/notifications', [SettingController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::get('/settings/storage', [SettingController::class, 'storage'])->name('settings.storage');
    Route::post('/settings/storage', [SettingController::class, 'updateStorage'])->name('settings.storage.update');
    Route::get('/settings/data', [SettingController::class, 'data'])->name('settings.data');
    Route::post('/settings/export', [SettingController::class, 'export'])->name('settings.export');

    // Riwayat Permintaan Penghapusan Pengguna
    Route::get('/deletion-requests', [UserDeletionRequestController::class, 'index'])->name('user-deletion-requests.index');
});

// Route Khusus untuk Admin - Manajemen User
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('deletion-requests', [DeletionApprovalController::class, 'index'])->name('deletion-requests.index');
    Route::post('deletion-requests/{deletionRequest}/approve', [DeletionApprovalController::class, 'approve'])->name('deletion-requests.approve');
    Route::post('deletion-requests/{deletionRequest}/reject', [DeletionApprovalController::class, 'reject'])->name('deletion-requests.reject');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});

require __DIR__.'/auth.php';
