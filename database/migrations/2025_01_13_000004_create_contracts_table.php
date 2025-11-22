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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('party_a')->nullable();
            $table->string('party_b')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->longText('contract_summary')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('effective_date');
            $table->index('expiration_date');
            $table->index('party_a');
            $table->index('party_b');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
