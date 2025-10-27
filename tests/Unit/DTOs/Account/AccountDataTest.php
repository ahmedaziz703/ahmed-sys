<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Account;

use App\DTOs\Account\AccountData;
use App\Models\Account;
use Illuminate\Http\Request;
use Tests\TestCase;

final class AccountDataTest extends TestCase
{
    public function test_it_can_be_created_from_request(): void
    {
        $request = new Request([
            'user_id' => 1,
            'name' => 'Test Account',
            'type' => 'bank_account',
            'currency' => 'YER',
            'balance' => 1000.50,
            'status' => true,
            'details' => ['bank_name' => 'Test Bank'],
            'account_id' => 1,
            'amount' => 500.25,
            'exchange_rate' => 1.0,
            'try_equivalent' => 500.25,
            'description' => 'Test Description',
            'transaction_date' => '2024-03-29',
            'category_id' => 1,
            'supplier_id' => 1,
            'installments' => 12,
            'remaining_installments' => 11,
            'monthly_amount' => 41.69,
            'next_payment_date' => '2024-04-29',
            'installment_count' => 12,
            'first_installment_date' => '2024-03-29',
        ]);

        $dto = AccountData::fromRequest($request);

        $this->assertEquals(1, $dto->user_id);
        $this->assertEquals('Test Account', $dto->name);
        $this->assertEquals('bank_account', $dto->type);
        $this->assertEquals('YER', $dto->currency);
        $this->assertEquals(1000.50, $dto->balance);
        $this->assertTrue($dto->status);
        $this->assertEquals(['bank_name' => 'Test Bank'], $dto->details);
        $this->assertEquals(1, $dto->account_id);
        $this->assertEquals(500.25, $dto->amount);
        $this->assertEquals(1.0, $dto->exchange_rate);
        $this->assertEquals(500.25, $dto->try_equivalent);
        $this->assertEquals('Test Description', $dto->description);
        $this->assertEquals('2024-03-29', $dto->transaction_date);
        $this->assertEquals(1, $dto->category_id);
        $this->assertEquals(1, $dto->supplier_id);
        $this->assertEquals(12, $dto->installments);
        $this->assertEquals(11, $dto->remaining_installments);
        $this->assertEquals(41.69, $dto->monthly_amount);
        $this->assertEquals('2024-04-29', $dto->next_payment_date);
        $this->assertEquals(12, $dto->installment_count);
        $this->assertEquals('2024-03-29', $dto->first_installment_date);
    }

    public function test_it_can_be_created_from_model(): void
    {
        $account = new Account([
            'user_id' => 1,
            'name' => 'Test Account',
            'type' => 'bank_account',
            'currency' => 'YER',
            'balance' => 1000.50,
            'status' => true,
            'details' => ['bank_name' => 'Test Bank'],
            'id' => 1,
        ]);

        $dto = AccountData::fromModel($account);

        $this->assertEquals(1, $dto->user_id);
        $this->assertEquals('Test Account', $dto->name);
        $this->assertEquals('bank_account', $dto->type);
        $this->assertEquals('YER', $dto->currency);
        $this->assertEquals(1000.50, $dto->balance);
        $this->assertTrue($dto->status);
        $this->assertEquals(['bank_name' => 'Test Bank'], $dto->details);
        $this->assertEquals(1, $dto->account_id);
        $this->assertNull($dto->amount);
        $this->assertNull($dto->exchange_rate);
        $this->assertNull($dto->try_equivalent);
        $this->assertNull($dto->description);
        $this->assertNull($dto->transaction_date);
        $this->assertNull($dto->category_id);
        $this->assertNull($dto->supplier_id);
        $this->assertNull($dto->installments);
        $this->assertNull($dto->remaining_installments);
        $this->assertNull($dto->monthly_amount);
        $this->assertNull($dto->next_payment_date);
        $this->assertNull($dto->installment_count);
        $this->assertNull($dto->first_installment_date);
    }

    public function test_it_can_be_created_from_array(): void
    {
        $data = [
            'user_id' => 1,
            'name' => 'Test Account',
            'type' => 'bank_account',
            'currency' => 'YER',
            'balance' => 1000.50,
            'status' => true,
            'details' => ['bank_name' => 'Test Bank'],
            'account_id' => 1,
            'amount' => 500.25,
            'exchange_rate' => 1.0,
            'try_equivalent' => 500.25,
            'description' => 'Test Description',
            'transaction_date' => '2024-03-29',
            'category_id' => 1,
            'supplier_id' => 1,
            'installments' => 12,
            'remaining_installments' => 11,
            'monthly_amount' => 41.69,
            'next_payment_date' => '2024-04-29',
            'installment_count' => 12,
            'first_installment_date' => '2024-03-29',
        ];

        $dto = AccountData::fromArray($data);

        $this->assertEquals(1, $dto->user_id);
        $this->assertEquals('Test Account', $dto->name);
        $this->assertEquals('bank_account', $dto->type);
        $this->assertEquals('YER', $dto->currency);
        $this->assertEquals(1000.50, $dto->balance);
        $this->assertTrue($dto->status);
        $this->assertEquals(['bank_name' => 'Test Bank'], $dto->details);
        $this->assertEquals(1, $dto->account_id);
        $this->assertEquals(500.25, $dto->amount);
        $this->assertEquals(1.0, $dto->exchange_rate);
        $this->assertEquals(500.25, $dto->try_equivalent);
        $this->assertEquals('Test Description', $dto->description);
        $this->assertEquals('2024-03-29', $dto->transaction_date);
        $this->assertEquals(1, $dto->category_id);
        $this->assertEquals(1, $dto->supplier_id);
        $this->assertEquals(12, $dto->installments);
        $this->assertEquals(11, $dto->remaining_installments);
        $this->assertEquals(41.69, $dto->monthly_amount);
        $this->assertEquals('2024-04-29', $dto->next_payment_date);
        $this->assertEquals(12, $dto->installment_count);
        $this->assertEquals('2024-03-29', $dto->first_installment_date);
    }

    public function test_it_can_be_converted_to_array(): void
    {
        $dto = new AccountData(
            user_id: 1,
            name: 'Test Account',
            type: 'bank_account',
            currency: 'YER',
            balance: 1000.50,
            status: true,
            details: ['bank_name' => 'Test Bank'],
            account_id: 1,
            amount: 500.25,
            exchange_rate: 1.0,
            try_equivalent: 500.25,
            description: 'Test Description',
            transaction_date: '2024-03-29',
            category_id: 1,
            supplier_id: 1,
            installments: 12,
            remaining_installments: 11,
            monthly_amount: 41.69,
            next_payment_date: '2024-04-29',
            installment_count: 12,
            first_installment_date: '2024-03-29',
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'user_id' => 1,
            'name' => 'Test Account',
            'type' => 'bank_account',
            'currency' => 'YER',
            'balance' => 1000.50,
            'status' => true,
            'details' => ['bank_name' => 'Test Bank'],
            'account_id' => 1,
            'amount' => 500.25,
            'exchange_rate' => 1.0,
            'try_equivalent' => 500.25,
            'description' => 'Test Description',
            'transaction_date' => '2024-03-29',
            'category_id' => 1,
            'supplier_id' => 1,
            'installments' => 12,
            'remaining_installments' => 11,
            'monthly_amount' => 41.69,
            'next_payment_date' => '2024-04-29',
            'installment_count' => 12,
            'first_installment_date' => '2024-03-29',
        ], $array);
    }
} 