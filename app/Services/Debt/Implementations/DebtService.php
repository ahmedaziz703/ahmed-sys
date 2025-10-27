<?php

declare(strict_types=1);

namespace App\Services\Debt\Implementations;

use App\Models\Debt;
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\DTOs\Debt\DebtData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\Payment\Implementations\PaymentService;

/**
 * Debt/credit service implementation
 * 
 * Contains methods required to manage debt/credit operations.
 * Handles creating, updating, and deleting debt/credit records.
 */
final class DebtService implements DebtServiceInterface
{
    private PaymentService $paymentService;

    /**
     * @param PaymentService $paymentService Payment service
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a new debt/credit record.
     * 
     * @param DebtData $data Debt/credit data
     * @return Debt Created debt/credit record
     */
    public function create(DebtData $data): Debt
    {
        return DB::transaction(function () use ($data) {
            $debt = Debt::create([
                'user_id' => $data->user_id,
                'customer_id' => $data->type === 'debt_payment' ? $data->customer_id : null,
                'supplier_id' => $data->type === 'loan_payment' ? $data->supplier_id : null,
                'type' => $data->type,
                'description' => $data->description,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'buy_price' => $data->buy_price,
                'due_date' => $data->due_date,
                'status' => $data->status,
                'notes' => $data->notes,
                'date' => $data->date,
            ]);

            $this->updateStatus($debt);
            $this->scheduleReminder($debt);
            return $debt;
        });
    }

    /**
     * Update an existing debt/credit record.
     * 
     * @param Debt $debt Debt/credit record to update
     * @param DebtData $data New debt/credit data
     * @return Debt Updated debt/credit record
     */
    public function update(Debt $debt, DebtData $data): Debt
    {
        return DB::transaction(function () use ($debt, $data) {
            $debt->update([
                'customer_id' => $data->type === 'debt_payment' ? $data->customer_id : null,
                'supplier_id' => $data->type === 'loan_payment' ? $data->supplier_id : null,
                'type' => $data->type,
                'description' => $data->description,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'buy_price' => $data->buy_price,
                'due_date' => $data->due_date,
                'status' => $data->status,
                'notes' => $data->notes,
                'date' => $data->date,
            ]);

            $this->updateStatus($debt);
            $this->scheduleReminder($debt);
            return $debt->fresh();
        });
    }

    /**
     * Delete a debt/credit record.
     * 
     * @param Debt $debt Debt/credit record to delete
     */
    public function delete(Debt $debt): void
    {
        DB::transaction(function () use ($debt) {
            $debt->delete();
        });
    }

    /**
     * Update the status of a debt/credit record.
     * 
     * Updates the status of a debt/credit record to 'overdue' if the due date has passed.
     * 
     * @param Debt $debt Debt/credit record to update
     */
    public function updateStatus(Debt $debt): void
    {
        if ($debt->due_date && Carbon::parse($debt->due_date)->startOfDay() < Carbon::now()->startOfDay() && $debt->status === 'pending') {
            $debt->update(['status' => 'overdue']);
        }
    }

    /**
     * Schedule reminders for debt/credit records.
     * 
     * Sends a reminder notification 3 days before the due date.
     * 
     * @param Debt $debt Debt/credit record to add reminder to
     */
    private function scheduleReminder(Debt $debt): void
    {
        if ($debt->due_date) {
            $reminderDate = Carbon::parse($debt->due_date)->subDays(3);
            if ($reminderDate->isFuture()) {
                Notification::make()
                    ->title('Borç/Alacak Hatırlatma')
                    ->body("Borç/Alacak #{$debt->id} vade تاريخi yaklaşıyor: {$debt->due_date->format('d.m.Y')}")
                    ->send();
            }
        }
    }

    /**
     * Get sorted debt/credit records.
     * 
     * @param string $sortBy Sorting field
     * @param string $direction Sorting direction
     * @return \Illuminate\Database\Eloquent\Collection Sorted debt/credit records
     */
    public function getSortedDebts(string $sortBy = 'due_date', string $direction = 'asc'): \Illuminate\Database\Eloquent\Collection
    {
        return Debt::whereIn('type', ['loan_payment', 'debt_payment'])
            ->orderBy($sortBy, $direction)
            ->get();
    }

    /*
     * Add a payment to a debt/credit record.
     * 
     * Calculates profit/loss for precious metals in grams, and for other currencies in units.
     * 
     * @param Debt $debt Debt/credit record to add payment to
     * @param array $data Payment data
     */
    /*
    public function addPayment(Debt $debt, array $data): void
    {
        DB::transaction(function () use ($debt, $data) {
            // Calculate sell price and profit/loss.
            $sellPrice = $data['sell_price'] ?? null;
            $profitLoss = null;

            if ($sellPrice && $debt->buy_price) {
                // Calculate profit/loss for precious metals in grams, and for other currencies in units.
                if (in_array($debt->currency, ['XAU', 'XAG'])) {
                    $profitLoss = ($sellPrice - $debt->buy_price) * $debt->amount; // Gram başına kar/zarar
                } else {
                    $profitLoss = ($sellPrice - $debt->buy_price) * $debt->amount; // Birim başına kar/zarar
                }
            }

            // Update the debt/credit record
            $debt->update([
                'sell_price' => $sellPrice,
                'profit_loss' => $profitLoss,
                'status' => 'completed',
            ]);

            // Process the payment
            $this->paymentService->processPayment($debt, $data, $data['payment_method']);
        });
    }
    */
}