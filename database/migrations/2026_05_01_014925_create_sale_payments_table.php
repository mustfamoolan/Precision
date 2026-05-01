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
        Schema::create('sale_payments', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sale_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('bank_id')->constrained();
            $blueprint->decimal('amount', 15, 2);
            $blueprint->date('date');
            $blueprint->string('note')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
