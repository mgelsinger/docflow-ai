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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', ['general', 'invoice', 'contract'])->default('general');
            $table->string('filename');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->enum('status', ['pending', 'processing', 'extracted', 'failed'])->default('pending');
            $table->longText('ocr_text')->nullable();
            $table->json('llm_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('category');
            $table->index('status');
            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
