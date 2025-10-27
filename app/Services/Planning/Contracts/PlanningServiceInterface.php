<?php

declare(strict_types=1);

namespace App\Services\Planning\Contracts;

use App\Models\SavingsPlan;
use App\Models\InvestmentPlan;

/**
 * Planning service interface
 * 
 * Defines the methods required for managing planning operations.
 * Handles savings and investment plan creation, updating, and deletion.
 */
interface PlanningServiceInterface
{
    /**
     * Create a new savings plan.
     * 
     * @param array $data Savings plan data
     * @return SavingsPlan Created savings plan
     */
    public function createSavingsPlan(array $data): SavingsPlan;

    /**
     * Update an existing savings plan.
     * 
     * @param SavingsPlan $plan Savings plan to update
     * @param array $data New savings plan data
     * @return SavingsPlan Updated savings plan
     */
    public function updateSavingsPlan(SavingsPlan $plan, array $data): SavingsPlan;

    /**
     * Delete a savings plan.
     * 
     * @param SavingsPlan $plan Savings plan to delete
     */
    public function deleteSavingsPlan(SavingsPlan $plan): void;

    /**
     * Create a new investment plan.
     * 
     * @param array $data Investment plan data
     * @return InvestmentPlan Created investment plan
     */
    public function createInvestmentPlan(array $data): InvestmentPlan;

    /**
     * Update an existing investment plan.
     * 
     * @param InvestmentPlan $plan Investment plan to update
     * @param array $data New investment plan data
     * @return InvestmentPlan Updated investment plan
     */
    public function updateInvestmentPlan(InvestmentPlan $plan, array $data): InvestmentPlan;

    /**
     * Delete an existing investment plan.
     * 
     * @param InvestmentPlan $plan Investment plan to delete
     */
    public function deleteInvestmentPlan(InvestmentPlan $plan): void;
} 