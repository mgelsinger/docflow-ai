<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'vendor_name',
        'vendor_address',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'currency',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Get the document that owns the invoice.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the line items for the invoice.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Scope a query to only include invoices for a specific vendor.
     */
    public function scopeVendor($query, string $vendorName)
    {
        return $query->where('vendor_name', 'like', "%{$vendorName}%");
    }

    /**
     * Scope a query to only include invoices within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->whereNull('paid_at');
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get the formatted total with currency.
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total, 2);
    }
}
