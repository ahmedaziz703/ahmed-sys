<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

/**
 * Loan Data Transfer Object
 * 
 * Used to transfer and convert loan data.
 * Used for loan creation, updating, and viewing.
 * Contains installment payments and loan tracking information.
 */
final class LoanData
{
    /**
     * @param int|null $user_id User ID
     * @param string $bank_name Bank name
     * @param string $loan_type Loan type
     * @param float $amount Loan amount
     * @param float $monthly_payment Monthly installment amount
     * @param int $installments Total installment count
     * @param int $remaining_installments Remaining installment count
     * @param string $start_date Start date
     * @param string $next_payment_date Next payment date
     * @param string|null $due_date Due date
     * @param float $remaining_amount Remaining loan amount
     * @param string $status Loan status
     * @param string|null $notes Notes
     */
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $bank_name,
        public readonly string $loan_type,
        public readonly float $amount,
        public readonly float $monthly_payment,
        public readonly int $installments,
        public readonly int $remaining_installments,
        public readonly string $start_date,
        public readonly string $next_payment_date,
        public readonly ?string $due_date,
        public readonly float $remaining_amount,
        public readonly string|bool $status,
        public readonly ?string $notes,
    ) {}

    /**
     * Create loan data from array
     * 
     * @param array $data Loan data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'] ?? auth()->id(),
            bank_name: $data['bank_name'],
            loan_type: $data['loan_type'],
            amount: (float) $data['amount'],
            monthly_payment: (float) $data['monthly_payment'],
            installments: (int) $data['installments'],
            remaining_installments: (int) ($data['remaining_installments'] ?? $data['installments']),
            start_date: $data['start_date'],
            next_payment_date: $data['next_payment_date'] ?? $data['start_date'],
            due_date: $data['due_date'] ?? null,
            remaining_amount: (float) ($data['remaining_amount'] ?? $data['amount']),
            status: $data['status'] ?? 'pending',
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert loan data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'bank_name' => $this->bank_name,
            'loan_type' => $this->loan_type,
            'amount' => $this->amount,
            'monthly_payment' => $this->monthly_payment,
            'installments' => $this->installments,
            'remaining_installments' => $this->remaining_installments,
            'start_date' => $this->start_date,
            'next_payment_date' => $this->next_payment_date,
            'due_date' => $this->due_date,
            'remaining_amount' => $this->remaining_amount,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}