<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Illuminate\Support\Carbon;

/**
 * Currency conversion service
 * 
 * Performs conversions between different currencies.
 * Calculates cross rates using TRY as the base.
 */
class CurrencyConversionService
{
    private CurrencyService $currencyService;

    /**
     * @param CurrencyService $currencyService Exchange rate service
     */
    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Convert an amount from one currency to another.
     * 
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source currency
     * @param string $toCurrency Target currency
     * @param Carbon $date Transaction date
     * @return float Converted amount
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, Carbon $date): float
    {
        // If same currency, return directly
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Convert to TRY
        $tryAmount = $this->convertToTRY($amount, $fromCurrency, $date);
        
        // Convert from TRY to target currency
        return $this->convertFromTRY($tryAmount, $toCurrency, $date);
    }

    /**
     * Convert an amount to TRY.
     * 
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source currency
     * @param Carbon $date Transaction date
     * @return float Amount in TRY
     */
    private function convertToTRY(float $amount, string $fromCurrency, Carbon $date): float
    {
        if ($fromCurrency === 'TRY') {
            return $amount;
        }

        $rates = $this->currencyService->getExchangeRates($date);
        if (!isset($rates[$fromCurrency])) {
            throw new \Exception("Kur bilgisi لم يتم العثور عليه: {$fromCurrency}");
        }

        return $amount * $rates[$fromCurrency]['buying'];
    }

    /**
     * Convert an amount in TRY to another currency.
     * 
     * @param float $tryAmount Amount in TRY
     * @param string $toCurrency Target currency
     * @param Carbon $date Transaction date
     * @return float Converted amount
     */
    private function convertFromTRY(float $tryAmount, string $toCurrency, Carbon $date): float
    {
        if ($toCurrency === 'TRY') {
            return $tryAmount;
        }

        $rates = $this->currencyService->getExchangeRates($date);
        if (!isset($rates[$toCurrency])) {
            throw new \Exception("Kur bilgisi لم يتم العثور عليه: {$toCurrency}");
        }

        return $tryAmount / $rates[$toCurrency]['selling'];
    }

    /**
     * Calculate the amount to deduct from account balance.
     * 
     * @param float $amount Transaction amount
     * @param string $transactionCurrency Transaction currency
     * @param string $accountCurrency Account currency
     * @param Carbon $date Transaction date
     * @return float Amount to deduct from account
     */
    public function calculateAccountDeduction(
        float $amount,
        string $transactionCurrency,
        string $accountCurrency,
        Carbon $date
    ): float {
        return $this->convert($amount, $transactionCurrency, $accountCurrency, $date);
    }
} 