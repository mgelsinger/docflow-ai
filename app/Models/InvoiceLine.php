<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * Get the invoice that owns the line item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate the line total based on quantity and unit price.
     */
    public function calculateTotal(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }

    /**
     * Get the formatted line total.
     */
    public function getFormattedLineTotalAttribute(): string
    {
        return number_format($this->line_total, 2);
    }

    /**
     * Boot method to auto-calculate line total.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($line) {
            if ($line->quantity && $line->unit_price) {
                $line->line_total = $line->calculateTotal();
            }
        });
    }
}
