<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('type')->default('local')->after('id'); // local, export
            $table->integer('items_count')->default(1)->after('customer_name');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('status')->default('paid')->after('due_amount'); // paid, partial, unpaid, pending
            $table->string('container_number')->nullable()->after('status');
            $table->string('shipping_status')->nullable()->after('container_number'); // On Board, In Transit, Delivered
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_number')->nullable()->after('id');
            $table->string('category')->default('Office')->after('description');
            $table->string('supplier_person')->nullable()->after('category');
            $table->string('payment_method')->default('Cash')->after('amount'); // Cash, Bank Transfer
            $table->string('status')->default('Paid')->after('payment_method'); // Paid, Partial, Unpaid
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['type', 'items_count', 'paid_amount', 'due_amount', 'status', 'container_number', 'shipping_status']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['expense_number', 'category', 'supplier_person', 'payment_method', 'status']);
        });
    }
};
