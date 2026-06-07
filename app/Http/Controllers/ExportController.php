<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function sales()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_report.csv"',
        ];

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Invoice #', 'Customer', 'Type', 'Amount', 'Paid', 'Due', 'Status']);

            Sale::latest('date')->latest('id')->chunk(100, function ($sales) use ($handle) {
                foreach ($sales as $sale) {
                    fputcsv($handle, [
                        $sale->date,
                        $sale->invoice_number,
                        $sale->customer_name,
                        $sale->type,
                        $sale->amount,
                        $sale->paid_amount,
                        $sale->due_amount,
                        $sale->status
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function expenses()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expenses_report.csv"',
        ];

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Ref #', 'Description', 'Employee', 'Amount', 'Payment Method']);

            Expense::with(['employee', 'bank'])->latest('date')->latest('id')->chunk(100, function ($expenses) use ($handle) {
                foreach ($expenses as $expense) {
                    fputcsv($handle, [
                        $expense->date,
                        $expense->expense_number,
                        $expense->description,
                        $expense->employee->name ?? 'N/A',
                        $expense->amount,
                        $expense->bank->name ?? $expense->payment_method
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function inventory()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_report.csv"',
        ];

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU', 'Name', 'Category', 'Shop Qty', 'Warehouse Qty', 'Remote Qty', 'Total Qty', 'Cost Price', 'Selling Price', 'Valuation']);

            Inventory::latest()->chunk(100, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->sku,
                        $item->name,
                        $item->category,
                        $item->shop_quantity,
                        $item->warehouse_quantity,
                        $item->remote_quantity,
                        $item->total_quantity,
                        $item->cost_price,
                        $item->selling_price,
                        $item->valuation
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
