<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category',
        'filename',
        'storage_path',
        'mime_type',
        'size_bytes',
        'status',
        'ocr_text',
        'llm_json',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'status' => DocumentStatus::class,
            'llm_json' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoice associated with the document.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Get the contract associated with the document.
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * Scope a query to only include documents of a specific category.
     */
    public function scopeCategory($query, DocumentCategory $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include documents with a specific status.
     */
    public function scopeStatus($query, DocumentStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include documents belonging to a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the document is an invoice.
     */
    public function isInvoice(): bool
    {
        return $this->category === DocumentCategory::INVOICE;
    }

    /**
     * Check if the document is a contract.
     */
    public function isContract(): bool
    {
        return $this->category === DocumentCategory::CONTRACT;
    }

    /**
     * Check if the document processing has completed.
     */
    public function isProcessed(): bool
    {
        return $this->status->isComplete();
    }

    /**
     * Check if the document processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status->isError();
    }
}
