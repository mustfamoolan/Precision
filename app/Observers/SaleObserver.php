<?php

namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        // Logic moved to SaleController to support individual payment records (SalePayment)
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Logic moved to SaleController to support individual payment records (SalePayment)
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        // When a sale is deleted, its payments are deleted via cascade
        // SalePayment model deleted event handles balance reversal
    }
}
