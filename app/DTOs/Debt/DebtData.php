<?php

declare(strict_types=1);

namespace App\DTOs\Debt;

/**
 * Debt Data Transfer Object
 * 
 * Used to transfer and convert debt data.
 * Used for debt creation, updating, and viewing.
 * Contains different calculation logic for precious metals and other currencies.
 */
final class DebtData
{
    /**
     * @param int|null $user_id User ID
     * @param int|null $customer_id Customer ID
     * @param int|null $supplier_id Supplier ID
     * @param string $type Debt type
     * @param string|null $description Description
     * @param float $amount Amount
     * @param string $currency Currency (default: YER)
     * @param float|null $buy_price Buy price
     * @param float|null $sell_price Sell price
     * @param float|null $profit_loss Profit/Loss
     * @param string|null $due_date Due date
     * @param string $status Status
     * @param string|null $notes Notes
     * @param string|null $date Date
     */
    public function __construct(
        public readonly ?int $user_id,
        public readonly ?int $customer_id,
        public readonly ?int $supplier_id,
        public readonly string $type,
        public readonly ?string $description,
        public readonly float $amount,
        public readonly string $currency = 'YER',
        public readonly ?float $buy_price = null,
        public readonly ?float $sell_price = null,
        public readonly ?float $profit_loss = null,
        public readonly ?string $due_date,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?string $date = null,
    ) {}

    /**
     * Create debt data from array
     * 
     * Contains different calculation logic for precious metals and other currencies.
     * 
     * @param array $data Debt data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? 'YER';
        $amount = (float) $data['amount'];
        $buyPrice = isset($data['buy_price']) ? (float) $data['buy_price'] : null;

        // Precious metals (XAU, XAG) in gram basis, other currencies in unit basis
        if (in_array($currency, ['XAU', 'XAG'])) {
            // Gram basis
            $amount = $amount;
            $buyPrice = $buyPrice; // Gram basis
        } else {
            // Unit basis
            $amount = $amount;
            $buyPrice = $buyPrice; // Unit basis
        }

        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : auth()->id(),
            customer_id: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            supplier_id: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            type: $data['type'],
            description: $data['description'] ?? null,
            amount: $amount,
            currency: $currency,
            buy_price: $buyPrice,
            sell_price: null,
            profit_loss: null,
            due_date: $data['due_date'] ?? null,
            status: $data['status'] ?? 'pending',
            notes: $data['notes'] ?? null,
            date: $data['date'] ?? now()->format('Y-m-d'),
        );
    }

    /**
     * Convert debt data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'type' => $this->type,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'profit_loss' => $this->profit_loss,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'date' => $this->date,
        ];
    }
}