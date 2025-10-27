<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Transaction Type Enum Class
 * 
 * Defines the supported financial transaction types.
 * Contains the Arabic label for each transaction type.
 */
enum TransactionTypeEnum: string
{
    /** Income */
    case INCOME = 'income';
    /** Expense */
    case EXPENSE = 'expense';
    /** Transfer */
    case TRANSFER = 'transfer';
    /** Installment */
    case INSTALLMENT = 'installment';
    /** اشتراك */
    case SUBSCRIPTION = 'subscription';
    /** Loan Payment */
    case LOAN_PAYMENT = 'loan_payment';

    /**
     * Returns the Arabic label for the transaction type
     * 
     * @return string Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::INCOME => 'إيراد',
            self::EXPENSE => 'مصروف',
            self::TRANSFER => 'تحويل',
            self::INSTALLMENT => 'دفعة مقسطة',
            self::SUBSCRIPTION => 'اشتراك',
            self::LOAN_PAYMENT => 'دفع قرض',
        };
    }
} 