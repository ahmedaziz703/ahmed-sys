<?php

namespace App\Enums;

/**
 * Transaction Status Enum Class
 * 
 * Defines the statuses of financial transactions.
 * Used to track the lifecycle of transactions.
 */
enum TransactionStatusEnum: string
{
    /** Beklemede */
    case PENDING = 'pending';
    /** Tamamlandı */
    case COMPLETED = 'completed';
    /** الغاء Edildi */
    case CANCELLED = 'cancelled';
    /** Başarısız */
    case FAILED = 'failed';
} 