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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('vendor_name')->nullable();
            $table->text('vendor_address')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('tax', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            // Indexes for common queries
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('vendor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
