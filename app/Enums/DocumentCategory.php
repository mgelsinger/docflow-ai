<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case GENERAL = 'general';
    case INVOICE = 'invoice';
    case CONTRACT = 'contract';

    /**
     * Get all category values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the category
     */
    public function label(): string
    {
        return match($this) {
            self::GENERAL => 'General Document',
            self::INVOICE => 'Invoice',
            self::CONTRACT => 'Contract',
        };
    }
}
