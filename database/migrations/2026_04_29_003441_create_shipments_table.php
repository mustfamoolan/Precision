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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('container_number')->unique();
            $table->string('vessel_name')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->date('departure_date')->nullable();
            $table->date('arrival_date')->nullable();
            $table->string('status')->default('On Board'); // On Board, In Transit, Delivered, Completed
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('import_tax', 15, 2)->default(0);
            $table->decimal('clearance_fees', 15, 2)->default(0);
            $table->decimal('other_costs', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
