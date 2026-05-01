<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('type')->nullable()->change(); // Make it nullable if it wasn't
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn(['cost', 'currency']);
        });
    }
};
