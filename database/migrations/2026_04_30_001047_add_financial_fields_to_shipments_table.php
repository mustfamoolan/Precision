<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('notes');
            $table->decimal('invoice_amount', 15, 2)->default(0)->after('supplier_name');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('invoice_amount');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['supplier_name', 'invoice_amount', 'paid_amount']);
        });
    }
};
