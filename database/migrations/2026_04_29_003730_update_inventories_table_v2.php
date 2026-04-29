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
        Schema::table('inventory', function (Blueprint $table) {
            $table->integer('remote_quantity')->default(0)->after('warehouse_quantity');
            $table->string('category')->nullable()->after('name');
            $table->decimal('cost_price', 15, 2)->default(0)->after('sku');
            $table->integer('low_stock_threshold')->default(10)->after('remote_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn(['remote_quantity', 'category', 'cost_price', 'low_stock_threshold']);
        });
    }
};
