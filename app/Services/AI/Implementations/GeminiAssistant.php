<?php

namespace App\Services\AI\Implementations;

use App\Models\User;
use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\DateRangeAnalyzer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Gemini Assistant
 *
 * Implements the AI assistant interface using the Gemini API.
 *
 * @return void
*/
class GeminiAssistant implements AIAssistantInterface
{
    /**
     * Constructor
     *
     * @param DateRangeAnalyzer $dateAnalyzer The date range analyzer.
     */
    private string $apiKey;
    /**
     * The model to use.
     */
    private string $model;
    /**
     * The configuration for the Gemini API.
     */
    private array $config;
    /**
     * The date range analyzer.
     */
    private DateRangeAnalyzer $dateAnalyzer;

    /**
     * Constructor
     *
     * @param string $apiKey The API key.
     * @param string $model The model to use.
     * @param array $config The configuration for the Gemini API.
     * @param DateRangeAnalyzer $dateAnalyzer The date range analyzer.
     */
    public function __construct(DateRangeAnalyzer $dateAnalyzer)
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->model = config('ai.gemini.model');
        $this->config = [
            'temperature' => config('ai.gemini.temperature'),
            'maxOutputTokens' => config('ai.gemini.max_tokens'),
        ];
        $this->dateAnalyzer = $dateAnalyzer;
    }

    public function query(User $user, string $question, ?string $conversationId = null): string
{
    try {
        // Determine date range based on question content
        $dateRange = $this->dateAnalyzer->analyze($question);
        
        // باقي الكود
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
            'contents' => $this->formatContents(config('ai.system_prompt'), $prompt, $history),
            'generationConfig' => $this->config
        ]);

        // سجل الاستجابة الحقيقية
        \Log::info('Gemini raw response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful()) {
            $result = $response->json();
            return $this->formatResponse(
                $result['candidates'][0]['content']['parts'][0]['text'] 
                ?? 'آسف، حدث خطأ أثناء إنشاء الرد.'
            );
        }

        Log::error('Gemini API Error', [
            'error' => $response->body(),
            'status' => $response->status()
        ]);

        return 'آسف، حدث خطأ. يرجى المحاولة مرة أخرى.';

    } catch (\Exception $e) {
        Log::error('Gemini Assistant Error', [
            'error' => $e->getMessage(),
            'user_id' => $user->id,
            'question' => $question,
            'conversation_id' => $conversationId
        ]);

        return 'آسف، حدث خطأ. يرجى المحاولة مرة أخرى.';
    }
}


    /**
     * Retrieve conversation messages for the given conversation ID from the database
     * while attempting to stay within a token budget.
     *
     * @param string|null $conversationId
     * @param int $maxTokenEstimate Maximum estimated token limit
     * @return array Array of past messages (role, content).
     */
    private function getConversationHistory(?string $conversationId, int $maxTokenEstimate = 2000): array
    {
        if (!$conversationId) {
            return [];
        }

        // Fetch the last 10 messages for the conversation (sufficient for context)
        $allMessages = DB::table('ai_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'role', 'content', 'created_at']);
        
        // If there are no messages, return an empty array
        if ($allMessages->isEmpty()) {
            return [];
        }
        
        $result = [];
        $estimatedTokens = 0;
        $tokenPerCharEstimate = 0.25; // Rough token estimate per character

        // Add messages starting from the latest, avoiding token budget overflow
        foreach ($allMessages as $message) {
            // Simple token estimate (a tokenizer would be more accurate)
            $contentLength = mb_strlen($message->content);
            $estimatedMessageTokens = (int)($contentLength * $tokenPerCharEstimate);
            
            // Stop if adding this message would exceed the token budget
            if ($estimatedTokens + $estimatedMessageTokens > $maxTokenEstimate) {
                break;
            }
            
            // Prepend message to preserve chronological order (old to new)
            array_unshift($result, [
                'role' => $message->role === 'ai' ? 'assistant' : $message->role,
                'content' => $message->content
            ]);
            
            $estimatedTokens += $estimatedMessageTokens;
        }
        
        // Ensure at least the last message is included for essential context
        if (empty($result) && !$allMessages->isEmpty()) {
            $lastMessage = $allMessages->first();
            $result[] = [
                'role' => $lastMessage->role === 'ai' ? 'assistant' : $lastMessage->role,
                'content' => $lastMessage->content
            ];
        }
        
        return $result;
    }

    /**
     * Build the contents payload for the Gemini API.
     * 
     * @param string $systemPrompt System prompt
     * @param string $userPrompt User prompt
     * @param array $history Conversation history
     * @return array Contents array for the Gemini API
     */
    private function formatContents(string $systemPrompt, string $userPrompt, array $history = []): array
    {
        $contents = [];
        
        // Add system prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $systemPrompt]
            ]
        ];
        
        // Append conversation history if available
        if (!empty($history)) {
            foreach ($history as $message) {
                if (!empty($message['role']) && !empty($message['content'])) {
                    $role = ($message['role'] === 'assistant') ? 'model' : 'user';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [
                            ['text' => $message['content']]
                        ]
                    ];
                }
            }
        }
        
        // Add the final user question
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userPrompt]
            ]
        ];
        
        return $contents;
    }

    private function calculateAggregatedStats(User $user, array $dateRange): array
    {
        $stats = [];
        $summary = [
            'income_total' => 0.0,
            'expense_total' => 0.0,
            'net_total' => 0.0
        ];
        
        $categoryStats = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('transactions.type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->select(
                'categories.name as category',
                'transactions.type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as total'),
                DB::raw('AVG(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as average'),
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month")
            )
            ->groupBy('categories.name', 'transactions.type', 'month')
            ->get();

        foreach ($categoryStats->groupBy('category') as $category => $types) {
            foreach ($types->groupBy('type') as $type => $months) {
                $totalAmount = $months->sum('total');
                $totalCount = $months->sum('count');
                
                $stats[$category][$type] = [
                    'toplam' => $this->formatCurrency($totalAmount),
                    'عملية_sayısı' => $totalCount,
                    'ortalama' => $totalCount > 0 ? $this->formatCurrency($totalAmount / $totalCount) : $this->formatCurrency(0),
                    'aylık_detay' => $months->mapWithKeys(function ($data) {
                        return [
                            $data->month => [
                                'toplam' => $this->formatCurrency($data->total),
                                'عملية_sayısı' => $data->count
                            ]
                        ];
                    })->toArray()
                ];
            }
        }

        $stats['summary'] = [
            'income_total' => $this->formatCurrency($summary['income_total']),
            'expense_total' => $this->formatCurrency($summary['expense_total']),
            'net_total' => $this->formatCurrency($summary['net_total'])
        ];

        return $stats;
    }

    /**
     * Format currency string.
     */
    private function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return '0,00 ل.ل';
        }
        return number_format(abs($amount), 2, ',', '.') . ' ل.ل';
    }

    private function getFilteredTransactions(User $user, array $dateRange): \Illuminate\Support\Collection
    {
        return DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('transactions.type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->orderBy('transactions.date', 'desc')
            ->get();
    }

    private function sanitizeData($transactions): array
    {
        return $transactions->map(function ($transaction) {
            // Type may be string or enum; read the value safely
            $type = is_object($transaction->type) && method_exists($transaction->type, 'value') 
                ? $transaction->type->value 
                : (string) $transaction->type;
                
            // Use try_equivalent for consistency
            $amount = $transaction->try_equivalent ?? $transaction->amount;
            
            // Convert string date to Carbon and format
            $date = $transaction->date instanceof Carbon 
                ? $transaction->date 
                : Carbon::parse($transaction->date);
                
            return [
                'type' => $type,
                'amount' => $this->formatCurrency($amount),
                'category' => $transaction->category ?? 'غير مصنف',
                'date' => $date->format('d.m.Y'),
                'description' => $this->maskSensitiveData($transaction->description)
            ];
        })->toArray();
    }

    private function buildPrompt(string $question, array $data, array $stats, array $dateRange, array $history): string
    {
        $prompt = "السؤال: {$question}\n\n";
        $prompt .= "الفترة المحللة:\n";
        $prompt .= "- البداية: " . Carbon::parse($dateRange['start'])->format('d.m.Y') . "\n";
        $prompt .= "- النهاية: " . Carbon::parse($dateRange['end'])->format('d.m.Y') . "\n";
        $prompt .= "- نوع الفترة: " . $this->getPeriodTypeText($dateRange['period_type']) . "\n\n";
        
        $prompt .= "ملاحظات مهمة:\n";
        $prompt .= "1. يرجى تنسيق مبالغ الليرة اللبنانية في ردودك باستخدام '.' كفاصل آلاف و ',' كفاصل عشري (مثال: 1.234,56 ل.ل)\n";
        $prompt .= "2. يتم عرض بيانات صاحب السؤال فقط.\n";
        $prompt .= "3. لم يتم تضمين معاملات التحويل في الحسابات.\n";
        $prompt .= "4. عند السؤال عن بيانات غير متوفرة، قم بالتحليل بناءً على البيانات المتاحة فقط واذكر ما هو مفقود. إذا سأل المستخدم عن بيانات مثل عمولة بطاقة الائتمان وهذه البيانات غير متوفرة، أجب: 'لا توجد لدي بيانات حول هذا الموضوع، يمكنني التحليل بناءً على المعاملات المتاحة فقط.'\n\n";
        
        $prompt .= "الإحصائيات حسب الفئة:\n" . json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "المعاملات:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return $prompt;
    }

    private function getPeriodTypeText(string $type): string
    {
        return match($type) {
            'month' => 'شهري',
            'year' => 'سنوي',
            'custom' => 'فترة مخصصة',
            default => 'غير محدد'
        };
    }

    private function formatResponse(string $response): string
    {
        return $response;
    }

    private function maskSensitiveData(?string $text): string
    {
        if (!$text) return '';
        
        $patterns = [
            '/\b\d{16}\b/' => '****-****-****-****',
            '/\bTR\d{24}\b/i' => 'TR**-****-****-****-****-****',
            '/\b\d{11}\b/' => '*****',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text);
    }
    
    /**
     * Analyze user message and generate an SQL query if needed.
     * 
     * @param mixed $user The user object.
     * @param string $message The user message.
     * @param array $databaseSchema The database schema (table and field information).
     * @return array ['query' => string, 'requires_sql' => bool, 'explanation' => string]
     */
    public function generateSqlQuery($user, string $message, array $databaseSchema): array
    {
        try {
            // Simple implementation - return false for requires_sql
            // You can implement AI-powered SQL generation here if needed
            return [
                'requires_sql' => false,
                'query' => '',
                'explanation' => 'لا يتطلب استعلام قاعدة بيانات'
            ];
        } catch (\Exception $e) {
            Log::error('SQL query generation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message
            ]);
            
            return [
                'requires_sql' => false,
                'query' => '',
                'explanation' => 'SQL sorgusu oluşturulamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a response using SQL results.
     * 
     * @param mixed $user The user object.
     * @param string $message The user message.
     * @param string $sqlQuery The executed SQL query.
     * @param array $sqlResults The SQL results.
     * @param string|null $conversationId The conversation ID.
     * @return string
     */
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, ?string $conversationId = null): string
    {
        return 'عذراً، هذه الميزة غير متوفرة حالياً مع Gemini';
    }
} 