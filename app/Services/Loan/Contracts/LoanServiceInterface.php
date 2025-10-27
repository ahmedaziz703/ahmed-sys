<?php

declare(strict_types=1);

namespace App\Services\Loan\Contracts;

use App\Models\Loan;
use App\Models\Transaction;
use App\DTOs\Loan\LoanData;

/**
 * Loan service interface
 * 
 * Defines the methods required for managing loan operations.
 * Handles loan creation, updating, deletion, and payment processing.
 */
interface LoanServiceInterface
{
    /**
     * Create a new loan record.
     * 
     * @param array $data Loan data
     * @return Loan Created loan record
     */
    public function createLoan(array $data): Loan;

    /**
     * Update an existing loan record.
     * 
     * @param Loan $loan Loan record to update
     * @param LoanData $data New loan data
     * @return Loan Updated loan record
     */
    public function update(Loan $loan, LoanData $data): Loan;

    /**
     * Add a payment to a loan record.
     * 
     * @param Loan $loan Loan record to add payment to
     * @param array $data Payment data
     */
    public function addPayment(Loan $loan, array $data): void;
    
    /**
     * Delete a loan record.
     * 
     * @param Loan $loan Loan record to delete
     * @return array Result and message
     */
    public function delete(Loan $loan): array;

    /*
     * Update a payment record.
     * 
     * @param Transaction $payment Payment record to update
     * @param array $data New payment data
     */
    //public function updatePayment(Transaction $payment, array $data): void;
    
    /*
     * Delete a payment record.
     * 
     * @param Transaction $payment Payment record to delete
     */
    //public function deletePayment(Transaction $payment): void;

    /*
     * Generate an installment plan.
     * 
     * @param Loan $loan Loan record to generate installment plan
     * @return array Installment plan details
     */
    //public function generateInstallmentPlan(Loan $loan): array;

    /*
     * Calculate the total amount with interest.
     * 
     * @param Loan $loan Loan record to calculate total amount
     * @return float Total amount
     */
    //public function calculateTotalWithInterest(Loan $loan): float;

    /*
     * Get loan details.
     * 
     * @param Loan $loan Loan record to get details
     * @return array Loan details
     */
    //public function getLoanDetails(Loan $loan): array;

    /*
     * Get next payment information.
     * 
     * @param Loan $loan Loan record to get next payment information
     * @return array Next payment information
     */
    //public function getNextPaymentInfo(Loan $loan): array;

    /*
     * Update the status of a loan record.
     * 
     * @param Loan $loan Loan record to update status
     */
    //public function updateStatus(Loan $loan): void;
}