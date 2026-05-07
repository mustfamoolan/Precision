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
    Route::put('/sales/{sale}', [\App\Http\Controllers\SaleController::class, 'update']);
    Route::post('/sales/{sale}/payments', [\App\Http\Controllers\SaleController::class, 'storePayment']);
    Route::delete('/sales/{sale}', [\App\Http\Controllers\SaleController::class, 'destroy']);
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store']);
    Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy']);
    Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees');
    Route::post('/employees', [\App\Http\Controllers\EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'destroy']);
    
    Route::post('/customers', [\App\Http\Controllers\CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'destroy']);
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory');
    Route::post('/inventory', [\App\Http\Controllers\InventoryController::class, 'store']);
    Route::put('/inventory/{inventory}', [\App\Http\Controllers\InventoryController::class, 'update']);
    Route::post('/inventory/{inventory}/transfer', [\App\Http\Controllers\InventoryController::class, 'transfer']);
    Route::post('/inventory/{inventory}/adjust', [\App\Http\Controllers\InventoryController::class, 'adjust']);
    Route::get('/inventory/{inventory}/history', [\App\Http\Controllers\InventoryController::class, 'history']);
    Route::post('/inventory/{inventory}/deduct', [\App\Http\Controllers\InventoryController::class, 'deductForCustomer']);
    Route::delete('/inventory/{inventory}', [\App\Http\Controllers\InventoryController::class, 'destroy']);
    Route::post('/brands', [\App\Http\Controllers\InventoryController::class, 'storeBrand']);
    Route::get('/reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders');
    Route::post('/reminders', [\App\Http\Controllers\ReminderController::class, 'store']);
    Route::put('/reminders/{reminder}', [\App\Http\Controllers\ReminderController::class, 'update']);
    Route::delete('/reminders/{reminder}', [\App\Http\Controllers\ReminderController::class, 'destroy']);
    Route::get('/shipping', [\App\Http\Controllers\ShippingToolController::class, 'index'])->name('shipping');
    Route::post('/shipping', [\App\Http\Controllers\ShippingToolController::class, 'store']);
    Route::get('/shipping/{shipment}', [\App\Http\Controllers\ShippingToolController::class, 'show'])->name('shipping.show');
    Route::put('/shipping/{shipment}', [\App\Http\Controllers\ShippingToolController::class, 'update']);
    Route::delete('/shipping/{shipment}', [\App\Http\Controllers\ShippingToolController::class, 'destroy']);
    
    Route::post('/shipping/{shipment}/items', [\App\Http\Controllers\ShippingToolController::class, 'storeItem']);
    Route::delete('/shipping/items/{item}', [\App\Http\Controllers\ShippingToolController::class, 'deleteItem']);
    
    Route::post('/shipping/{shipment}/payments', [\App\Http\Controllers\ShippingToolController::class, 'storePayment']);
    Route::delete('/shipping/payments/{payment}', [\App\Http\Controllers\ShippingToolController::class, 'deletePayment']);

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications');
    Route::get('/api/notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/clear-all', [\App\Http\Controllers\NotificationController::class, 'clearAll']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy']);
    
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports');
    Route::get('/export/sales', [\App\Http\Controllers\ExportController::class, 'sales'])->name('export.sales');
    Route::get('/export/expenses', [\App\Http\Controllers\ExportController::class, 'expenses'])->name('export.expenses');
    Route::get('/export/inventory', [\App\Http\Controllers\ExportController::class, 'inventory'])->name('export.inventory');
    
    // Bank System Routes
    Route::get('/banks', [\App\Http\Controllers\BankController::class, 'index'])->name('banks');
    Route::post('/banks', [\App\Http\Controllers\BankController::class, 'store']);
    Route::put('/banks/{bank}', [\App\Http\Controllers\BankController::class, 'update']);
    Route::post('/banks/adjust', [\App\Http\Controllers\BankController::class, 'adjustBalance']);
    Route::post('/banks/expense', [\App\Http\Controllers\BankController::class, 'storeExpense']);
    Route::post('/cheques', [\App\Http\Controllers\ChequeController::class, 'store']);
    Route::post('/cheques/{cheque}/receive', [\App\Http\Controllers\ChequeController::class, 'receive']);
    Route::post('/cheques/{cheque}/clear', [\App\Http\Controllers\ChequeController::class, 'clear']);
    Route::delete('/cheques/{cheque}', [\App\Http\Controllers\ChequeController::class, 'destroy']);

    Route::get('/activity-log', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-log');
});
