<?php

declare(strict_types=1);

namespace App\Services\Currency;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Exchange rate service
 * 
 * Fetches, caches, and manages exchange rates from the Central Bank of Turkey (TCMB).
 * Contains methods to retrieve rates and calculate cross rates.
 */
final class CurrencyService
{
    private const CACHE_KEY = 'currency_rates';
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_RETRY_DAYS = 12; // Look back up to 12 days
    
    /**
     * Get exchange rates for a given date.
     * 
     * @param Carbon|null $date Date
     * @return array|null Exchange rates
     */
    public function getExchangeRates(?Carbon $date = null): ?array
    {
        $date = $date ?? now();
        $cacheKey = self::CACHE_KEY . '_' . $date->format('Y-m-d');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            return $this->fetchRatesWithFallback($date);
        });
    }

    /**
     * Fetch rates for a specific date with fallback mechanism.
     * 
     * @param Carbon $date Date
     * @return array|null Exchange rates
     */
    private function fetchRatesWithFallback(Carbon $date): ?array
    {
        $tryDate = $date->copy();
        $attempts = 0;

        while ($attempts < self::MAX_RETRY_DAYS) {
            // Weekend check
            if ($tryDate->isWeekend()) {
                $tryDate = $tryDate->copy()->previous('Friday');
                continue;
            }

            // If data is missing due to holidays or other reasons, try the previous day
            $rates = $this->fetchRatesForDate($tryDate);
            
            if ($rates !== null) {

                return $rates;
            }



            $tryDate->subDay();
            $attempts++;
        }

        return $this->getDefaultRates();
    }

    /**
     * Fetch exchange rates from TCMB for a given date.
     * 
     * @param Carbon $date Date
     * @return array|null Exchange rates
     */
    private function fetchRatesForDate(Carbon $date): ?array
    {
        try {
            if ($date->isToday()) {
                $url = 'https://www.tcmb.gov.tr/kurlar/today.xml';
            } else {
                // TCMB format: /202403/13032024.xml
                $url = sprintf(
                    'https://www.tcmb.gov.tr/kurlar/%s/%s.xml',
                    $date->format('Y') . $date->format('m'),  // Year and month: 202403
                    $date->format('d') . $date->format('m') . $date->format('Y')  // DayMonthYear: 13032024
                );
            }

            $response = Http::get($url);
            
            if (!$response->successful()) {
                return null;
            }

            $xml = new SimpleXMLElement($response->body());
            $rates = $this->parseXmlRates($xml);

            return $rates;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse exchange rates from XML format.
     * 
     * @param SimpleXMLElement $xml XML data
     * @return array Parsed rate data
     */
    private function parseXmlRates(SimpleXMLElement $xml): array
    {
        $rates = [];
        
        foreach ($xml->Currency as $currency) {
            $code = (string) $currency['CurrencyCode'];
            $buying = (float) $currency->ForexBuying;
            $selling = (float) $currency->ForexSelling;
            
            if ($buying > 0 && $selling > 0) {
                $rates[$code] = [
                    'buying' => $buying,
                    'selling' => $selling,
                    'code' => $code,
                    'name' => (string) $currency->CurrencyName,
                    'unit' => (int) $currency->Unit,
                ];
            }
        }

        return $rates;
    }

    /**
     * Return default exchange rates.
     * 
     * @return array Default rates
     */
    private function getDefaultRates(): array
    {
        return [
            'USD' => [
                'buying' => 36,
                'selling' => 36,
                'code' => 'USD',
                'name' => 'US DOLLAR',
                'unit' => 1,
            ],
            'EUR' => [
                'buying' => 39,
                'selling' => 39,
                'code' => 'EUR',
                'name' => 'EURO',
                'unit' => 1,
            ],
            'GBP' => [
                'buying' => 47,
                'selling' => 47,
                'code' => 'GBP',
                'name' => 'İNGİLİZ STERLİNİ',
                'unit' => 1,
            ],
        ];
    }

    /**
     * Get exchange rate info for a specific currency.
     * 
     * @param string $currencyCode Currency code
     * @param Carbon|null $date Date
     * @return array|null Rate info
     */
    public function getExchangeRate(string $currencyCode, ?Carbon $date = null): ?array
    {
        // Special case for TRY
        if ($currencyCode === 'YER') {
            return [
                'buying' => 1,
                'selling' => 1,
                'code' => 'YER',
                'name' => 'ريال يمني',
                'unit' => 1,
            ];
        }

        $rates = $this->getExchangeRates($date);
        return $rates[$currencyCode] ?? null;
    }

    /**
     * Calculate cross rate between two currencies.
     * 
     * @param string $fromCurrency Source currency
     * @param string $toCurrency Target currency
     * @param array $rates Rate info
     * @return float Cross rate
     */
    public function calculateCrossRate(string $fromCurrency, string $toCurrency, array $rates): float
    {
        // Same currency
        if ($fromCurrency === $toCurrency) {
            return 1;
        }

        // TRY -> Other
        if ($fromCurrency === 'YER') {
            return 1 / $rates[$toCurrency]['selling'];
        }

        // Other -> TRY
        if ($toCurrency === 'YER') {
            return $rates[$fromCurrency]['buying'];
        }

        // Other -> Other (via USD)
        $fromUsdRate = $rates[$fromCurrency]['buying'] / $rates['USD']['buying'];
        $toUsdRate = $rates[$toCurrency]['selling'] / $rates['USD']['selling'];
        
        return $fromUsdRate / $toUsdRate;
    }
} 