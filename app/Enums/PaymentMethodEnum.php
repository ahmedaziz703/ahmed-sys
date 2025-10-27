<?php

namespace App\Enums;

/**
 * Payment Method Enum Class
 * 
 * Defines the supported payment methods in the system.
 * Contains the Turkish label and conversion methods for each payment method.
 */
enum PaymentMethodEnum: string
{
    /** Cash Payment */
    case CASH = 'cash';
    /** Bank Account */
    case BANK = 'bank';
    /** Credit Card */
    case CREDIT_CARD = 'credit_card';
    /** Crypto Wallet */
    case CRYPTO = 'crypto';
    /** Virtual POS */
    case VIRTUAL_POS = 'virtual_pos';

    /**
     * Returns the Turkish label for the payment method
     * 
     * @return string Turkish label
     */
    public function label(): string
    {
        return match($this) {
            self::CASH => 'نقدًا',
            self::BANK => 'حساب مصرفي',
            self::CREDIT_CARD => 'بطاقة ائتمان',
            self::CRYPTO => 'محفظة العملات المشفرة',
            self::VIRTUAL_POS => 'نقاط البيع الافتراضية',
        };
    }

    /**
     * Returns all payment methods with their labels as an array
     * 
     * @return array<string, string> Payment methods and labels
     */
    public static function toArray(): array
    {
        return [
            self::CASH->value => self::CASH->label(),
            self::BANK->value => self::BANK->label(),
            self::CREDIT_CARD->value => self::CREDIT_CARD->label(),
            self::CRYPTO->value => self::CRYPTO->label(),
            self::VIRTUAL_POS->value => self::VIRTUAL_POS->label(),
        ];
    }
} 