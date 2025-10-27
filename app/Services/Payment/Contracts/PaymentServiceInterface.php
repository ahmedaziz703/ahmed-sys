<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

/**
 * Payment service interface
 * 
 * Defines the methods required for managing payment operations.
 * Handles payment processing, validation, status checks, and transfer between accounts.
 */
interface PaymentServiceInterface
{
    /**
     * Process a payment.
     * 
     * @param mixed $entity Entity to pay (Debt, Loan, Account, Transaction)
     * @param array $data Payment data
     * @param string $paymentMethod Payment method
     */
    public function processPayment($entity, array $data, string $paymentMethod): void;

    /**
     * Validate payment data.
     * 
     * @param array $data Payment data to validate
     * @return bool Validity of the data
     */
    public function validatePayment(array $data): bool;

    /**
     * Get payment status.
     * 
     * @param string $paymentId Payment ID
     * @return string Payment status
     */
    public function getPaymentStatus(string $paymentId): string;

    /**
     * Transfer between accounts.
     * 
     * @param int $sourceAccountId Source account ID
     * @param int $targetAccountId Target account ID
     * @param float $amount Transfer amount
     * @param string|null $description Transfer description
     */
    public function transferBetweenAccounts(int $sourceAccountId, int $targetAccountId, float $amount, ?string $description = null): void;
} 