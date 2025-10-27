<?php

declare(strict_types=1);

namespace App\DTOs\Account;

use App\Models\Account;
use Illuminate\Http\Request;

/**
 * Account Data Transfer Object
 * 
 * Used to transfer and convert account data.
 * Used for account creation, updating, and viewing.
 */
final class AccountData
{
    /**
     * @param string $name Account name
     * @param string $type Account type
     * @param string $currency Currency
     * @param float|null $balance Balance
     * @param string|null $description Description
     * @param bool|null $status Status
     * @param int|null $user_id User ID
     * @param int|null $id Account ID
     * @param array|null $details Details
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $currency,
        public readonly ?float $balance = 0,
        public readonly ?string $description = null,
        public readonly ?bool $status = true,
        public readonly ?int $user_id = null,
        public readonly ?int $id = null,
        public readonly ?array $details = null,
    ) {}

    /**
     * Create account data from request
     * 
     * @param Request $request Request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            user_id: $request->input('user_id'),
            name: $request->string('name')->toString(),
            type: $request->string('type')->toString(),
            currency: $request->string('currency')->toString(),
            balance: $request->input('balance') ? (float) $request->input('balance') : null,
            status: $request->boolean('status'),
            details: $request->input('details'),
            description: $request->input('description'),
        );
    }

    /**
     * Create account data from model
     * 
     * @param Account $account Account model
     * @return self
     */
    public static function fromModel(Account $account): self
    {
        return new self(
            user_id: $account->user_id,
            name: $account->name,
            type: $account->type,
            currency: $account->currency,
            balance: $account->balance,
            status: $account->status,
            details: $account->details,
            description: $account->description,
            id: $account->id,
        );
    }

    /**
     * Create account data from array
     * 
     * @param array $data Account data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'] ?? null,
            name: $data['name'],
            type: $data['type'],
            currency: $data['currency'],
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            status: isset($data['status']) ? (bool) $data['status'] : null,
            details: $data['details'] ?? null,
            description: $data['description'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    /**
     * Convert account data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'status' => $this->status,
            'details' => $this->details,
            'description' => $this->description,
            'id' => $this->id,
        ];
    }
} 