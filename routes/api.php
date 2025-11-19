<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ActivityLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Public routes (no authentication required)
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (auth()->attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        
        $response = response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'role' => auth()->user()->role,
            ],
        ]);
        
        // Ensure session cookie is set for API requests
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }

    return response()->json([
        'message' => 'Invalid credentials',
    ], 401);
})->name('api.login');

// Get CSRF token for API requests
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->name('api.csrf-token');

Route::post('/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return response()->json([
        'message' => 'Logout successful',
    ]);
})->middleware('auth')->name('api.logout');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');
    
    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('api.transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('api.transactions.show');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('api.transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('api.transactions.destroy');
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('api.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');
    
    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('api.invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('api.invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('api.invoices.show');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('api.invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('api.invoices.destroy');
    
    // Debts
    Route::get('/debts', [DebtController::class, 'index'])->name('api.debts.index');
    Route::post('/debts', [DebtController::class, 'store'])->name('api.debts.store');
    Route::post('/debts/sync-missing', [DebtController::class, 'syncMissingDebts'])->name('api.debts.sync-missing');
    Route::get('/debts/{debt}', [DebtController::class, 'show'])->name('api.debts.show');
    Route::put('/debts/{debt}', [DebtController::class, 'update'])->name('api.debts.update');
    Route::delete('/debts/{debt}', [DebtController::class, 'destroy'])->name('api.debts.destroy');
    Route::post('/debts/{debt}/pay', [DebtController::class, 'storePayment'])->name('api.debts.pay');
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('api.reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('api.reports.export');
    
    // Users (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('api.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('api.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('api.users.destroy');
        
        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('api.activity-logs.index');
    });
});

