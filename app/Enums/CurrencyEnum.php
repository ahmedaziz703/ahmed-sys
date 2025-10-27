<?php

namespace App\Enums;

/**
 * Currency Enum Class
 * 
 * Defines the supported currencies in the system.
 * Contains color and symbol information for each currency.
 */
enum CurrencyEnum: string
{
    /** Turkish Lira */
    case YER = 'YER';
    /** American Dollar */
    case USD = 'USD';
    /** Euro */
    case EUR = 'EUR';
    /** British Pound */
    case GBP = 'GBP';

    /**
     * Returns the color code for the currency's visual representation
     * 
     * @return string RGB color code
     */
    public function color(): string
    {
        return match($this) {
            self::YER => 'rgb(230, 25, 75)',  // Red
            self::USD => 'rgb(60, 180, 75)',   // Green
            self::EUR => 'rgb(0, 130, 200)',   // Blue
            self::GBP => 'rgb(245, 130, 48)',  // Orange
        };
    }

    /**
     * Returns the symbol for the currency
     * 
     * @return string Currency symbol
     */
    public function symbol(): string
    {
        return match($this) {
            self::YER => 'ر.ي',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
        };
    }
} 