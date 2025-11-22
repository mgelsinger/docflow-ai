<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'party_a',
        'party_b',
        'effective_date',
        'expiration_date',
        'contract_summary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    /**
     * Get the document that owns the contract.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Scope a query to only include active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            });
    }

    /**
     * Scope a query to only include expired contracts.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiration_date', '<', now());
    }

    /**
     * Scope a query to only include contracts for a specific party.
     */
    public function scopeParty($query, string $partyName)
    {
        return $query->where(function ($q) use ($partyName) {
            $q->where('party_a', 'like', "%{$partyName}%")
                ->orWhere('party_b', 'like', "%{$partyName}%");
        });
    }

    /**
     * Scope a query to only include contracts expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereBetween('expiration_date', [now(), now()->addDays($days)]);
    }

    /**
     * Check if the contract is currently active.
     */
    public function isActive(): bool
    {
        $now = now();

        if ($this->effective_date && $this->effective_date->isFuture()) {
            return false;
        }

        if ($this->expiration_date && $this->expiration_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the contract has expired.
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Check if the contract is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isFuture()
            && $this->expiration_date->diffInDays(now()) <= $days;
    }

    /**
     * Get the number of days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if (!$this->expiration_date) {
            return null;
        }

        return max(0, $this->expiration_date->diffInDays(now(), false));
    }
}
