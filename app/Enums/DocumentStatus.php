<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case EXTRACTED = 'extracted';
    case FAILED = 'failed';

    /**
     * Get all status values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::EXTRACTED => 'Extracted',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Check if the status indicates completion
     */
    public function isComplete(): bool
    {
        return in_array($this, [self::EXTRACTED, self::FAILED]);
    }

    /**
     * Check if the status indicates an error state
     */
    public function isError(): bool
    {
        return $this === self::FAILED;
    }
}
