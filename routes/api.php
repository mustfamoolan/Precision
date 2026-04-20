<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShippingToolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Banks
    Route::get('banks/overview', [BankController::class, 'overview']);
    Route::apiResource('banks', BankController::class);

    // Sales
    Route::get('sales/totals', [SaleController::class, 'totals']);
    Route::apiResource('sales', SaleController::class);

    // Expenses
    Route::get('expenses/totals', [ExpenseController::class, 'totals']);
    Route::apiResource('expenses', ExpenseController::class);

    // Inventory
    Route::post('inventory/{inventory}/transfer', [InventoryController::class, 'transfer']);
    Route::apiResource('inventory', InventoryController::class);

    // Reminders
    Route::apiResource('reminders', ReminderController::class);

    // Cheques
    Route::get('cheques/upcoming', [ChequeController::class, 'upcoming']);
    Route::post('cheques/{cheque}/receive', [ChequeController::class, 'receive']);
    Route::apiResource('cheques', ChequeController::class);

    // Reports
    Route::get('reports/weekly', [ReportController::class, 'weekly']);

    // Shipping Tool (Logic only endpoint)
    Route::post('shipping/calculate', [ShippingToolController::class, 'calculate']);
});
