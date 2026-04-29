<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\LoginController;

// Auth Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Redirect root to dashboard (will trigger auth check)
Route::get('/', function () {
    return redirect('/dashboard');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sales', [\App\Http\Controllers\SaleController::class, 'index'])->name('sales');
    Route::post('/sales', [\App\Http\Controllers\SaleController::class, 'store']);
    Route::delete('/sales/{sale}', [\App\Http\Controllers\SaleController::class, 'destroy']);
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store']);
    Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy']);
    Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees');
    Route::post('/employees', [\App\Http\Controllers\EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'destroy']);
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory', [\App\Http\Controllers\InventoryController::class, 'store']);
    Route::put('/inventory/{inventory}', [\App\Http\Controllers\InventoryController::class, 'update']);
    Route::post('/inventory/{inventory}/transfer', [\App\Http\Controllers\InventoryController::class, 'transfer']);
    Route::delete('/inventory/{inventory}', [\App\Http\Controllers\InventoryController::class, 'destroy']);
    Route::get('/reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders');
    Route::post('/reminders', [\App\Http\Controllers\ReminderController::class, 'store']);
    Route::put('/reminders/{reminder}', [\App\Http\Controllers\ReminderController::class, 'update']);
    Route::delete('/reminders/{reminder}', [\App\Http\Controllers\ReminderController::class, 'destroy']);
    Route::get('/shipping', [\App\Http\Controllers\ShippingToolController::class, 'index'])->name('shipping');
    Route::post('/shipping', [\App\Http\Controllers\ShippingToolController::class, 'store']);
    Route::put('/shipping/{shipment}', [\App\Http\Controllers\ShippingToolController::class, 'update']);
    Route::delete('/shipping/{shipment}', [\App\Http\Controllers\ShippingToolController::class, 'destroy']);

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications');
    Route::get('/api/notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/export/sales', [\App\Http\Controllers\ExportController::class, 'sales'])->name('export.sales');
    Route::get('/export/expenses', [\App\Http\Controllers\ExportController::class, 'expenses'])->name('export.expenses');
    Route::get('/export/inventory', [\App\Http\Controllers\ExportController::class, 'inventory'])->name('export.inventory');
    
    // Bank System Routes
    Route::get('/banks', [\App\Http\Controllers\BankController::class, 'index'])->name('banks');
    Route::post('/banks/expense', [\App\Http\Controllers\BankController::class, 'storeExpense']);
    Route::post('/cheques', [\App\Http\Controllers\ChequeController::class, 'store']);
    Route::post('/cheques/{cheque}/receive', [\App\Http\Controllers\ChequeController::class, 'receive']);
    Route::delete('/cheques/{cheque}', [\App\Http\Controllers\ChequeController::class, 'destroy']);
});
