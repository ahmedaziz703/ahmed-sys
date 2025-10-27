<?php

namespace App\Services\AI;

use Carbon\Carbon;
use Illuminate\Support\Str;

class DateRangeAnalyzer
{
    private array $monthNames = [
        'يناير' => 1, 'فبراير' => 2, 'مارس' => 3, 'أبريل' => 4, 'مايو' => 5, 'يونيو' => 6,
        'يوليو' => 7, 'أغسطس' => 8, 'سبتمبر' => 9, 'أكتوبر' => 10, 'نوفمبر' => 11, 'ديسمبر' => 12,
        'يناير' => 1, 'فبراير' => 2, 'مارس' => 3, 'أبريل' => 4, 'مايو' => 5, 'يونيو' => 6,
        'يوليو' => 7, 'أغسطس' => 8, 'سبتمبر' => 9, 'أكتوبر' => 10, 'نوفمبر' => 11, 'ديسمبر' => 12
    ];

    public function analyze(string $message): array
    {
        $message = mb_strtolower($message);
        
        // Detect year
        preg_match('/\b(20\d{2})\b/', $message, $yearMatches);
        $year = $yearMatches[1] ?? date('Y');
        
        // Detect month
        $month = null;
        foreach ($this->monthNames as $name => $number) {
            if (Str::contains($message, $name)) {
                $month = $number;
                break;
            }
        }

        // Special cases
        if (Str::contains($message, 'bu yıl')) {
            $year = date('Y');
        }
        
        if (Str::contains($message, 'geçen yıl')) {
            $year = (int)date('Y') - 1;
        }

        // Determine date range
        if ($month) {
            // For a specific month
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        } else {
            // For a specific year
            $start = Carbon::create($year, 1, 1)->startOfYear();
            
            // If the year is in the future, cap at today
            if ($year > date('Y')) {
                $end = now();
            } else {
                $end = $start->copy()->endOfYear();
            }
        }

        // Phrases like "since ..."
        if (Str::contains($message, ['dan bu yana', 'den bu yana', 'dan beri', 'den beri'])) {
            if (preg_match('/(\d+)\s*\.\s*ay/', $message, $matches)) {
                $start = now()->subMonths($matches[1])->startOfMonth();
            } elseif ($month) {
                $start = Carbon::create($year, $month, 1)->startOfMonth();
            }
            $end = now();
        }

        // Ensure start date is not in the future
        if ($start->isFuture()) {
            $start = now()->startOfDay();
        }

        // Ensure end date is not before start date
        if ($end->lt($start)) {
            $end = $start->copy()->endOfMonth();
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'is_single_month' => $month !== null,
            'year' => $year,
            'month' => $month,
            'period_type' => $this->determinePeriodType($start, $end)
        ];
    }

    private function determinePeriodType(Carbon $start, Carbon $end): string
    {
        if ($start->format('Y-m') === $end->format('Y-m')) {
            return 'month';
        }

        if ($start->format('Y') === $end->format('Y') && 
            $start->format('m') === '01' && 
            $end->format('m') === '12') {
            return 'year';
        }

        return 'custom';
    }
} 