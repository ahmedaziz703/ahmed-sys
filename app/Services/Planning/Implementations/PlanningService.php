<?php

declare(strict_types=1);

namespace App\Services\Planning\Implementations;

use App\Models\SavingsPlan;
use App\Models\InvestmentPlan;
use App\Services\Planning\Contracts\PlanningServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Planning service implementation
 * 
 * Contains methods required to manage planning operations.
 * Handles creating, updating, and deleting savings and investment plans.
 */
final class PlanningService implements PlanningServiceInterface
{
    /**
     * Create a new savings plan.
     * 
     * @param array $data Savings plan data
     * @return SavingsPlan Created savings plan
     */
    public function createSavingsPlan(array $data): SavingsPlan
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            return SavingsPlan::create($data);
        });
    }

    /**
     * Update an existing savings plan.
     * 
     * @param SavingsPlan $plan Savings plan to update
     * @param array $data New savings plan data
     * @return SavingsPlan Updated savings plan
     */
    public function updateSavingsPlan(SavingsPlan $plan, array $data): SavingsPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update($data);
            return $plan->fresh();
        });
    }

    /**
     * Delete a savings plan.
     * 
     * @param SavingsPlan $plan Savings plan to delete
     */
    public function deleteSavingsPlan(SavingsPlan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->delete();
        });
    }

    /**
     * Create a new investment plan.
     * 
     * @param array $data Investment plan data
     * @return InvestmentPlan Created investment plan
     */
    public function createInvestmentPlan(array $data): InvestmentPlan
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            return InvestmentPlan::create($data);
        });
    }

    /**
     * Update an existing investment plan.
     * 
     * @param InvestmentPlan $plan Investment plan to update
     * @param array $data New investment plan data
     * @return InvestmentPlan Updated investment plan
     */
    public function updateInvestmentPlan(InvestmentPlan $plan, array $data): InvestmentPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update($data);
            return $plan->fresh();
        });
    }

    /**
     * Delete an existing investment plan.
     * 
     * @param InvestmentPlan $plan Investment plan to delete
     */
    public function deleteInvestmentPlan(InvestmentPlan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->delete();
        });
    }
} 