<?php

namespace App\Services\AI\Implementations;

use App\Models\User;
use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\DateRangeAnalyzer;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * OpenAI Assistant
 *
 * Implements the AI assistant interface using the OpenAI API.
 *
 * @return void
*/
class OpenAIAssistant implements AIAssistantInterface
{
    public function __construct(
        private readonly Client $openai,
        private readonly DateRangeAnalyzer $dateAnalyzer
    ) {}

    /**
     * Send user's question to the AI model and return the response.
     *
     * @param User $user The user asking the question
     * @param string $question The user's question
     * @param string|null $conversationId Existing conversation ID (if any)
     * @return string The AI model's response
     */
    public function query(User $user, string $question, ?string $conversationId = null): string
    {
        try {
            // Determine date range based on the question
            $dateRange = $this->dateAnalyzer->analyze($question);
            
            // Calculate category-based statistics and overall totals
            $stats = $this->calculateAggregatedStats($user, $dateRange);
            
            // Retrieve detailed transaction data
            $transactions = $this->getFilteredTransactions($user, $dateRange);
            
            // Mask sensitive data and prepare the payload
            $sanitizedData = $this->sanitizeData($transactions);
            
            // Retrieve conversation history
            $history = $this->getConversationHistory($conversationId);

            // Build AI prompt as a message array along with conversation history
            $messages = $this->buildPrompt(
                $question,
                $sanitizedData,
                $stats, 
                $dateRange,
                $history
            );
            
            // Call the OpenAI API
            $response = $this->openai->chat()->create([
                'model' => config('ai.openai.model'),
                'messages' => $messages, // Send the message array directly
                'temperature' => (float) config('ai.openai.temperature'),
                'max_tokens' => (int) config('ai.openai.max_tokens')
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('OpenAI API Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'question' => $question,
                'conversation_id' => $conversationId
            ]);

            if (str_contains($e->getMessage(), 'api_key')) {
                return 'حدثت مشكلة تتعلق بمفتاح API. يُرجى التواصل مع مسؤول النظام.';
            }

            if (str_contains($e->getMessage(), 'rate_limit') || str_contains($e->getMessage(), 'rate limit')) {
                return 'عذراً، تم الوصول إلى الحد الأقصى من الطلبات المسموح بها. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى.';
            }

            if (str_contains($e->getMessage(), 'model') || str_contains($e->getMessage(), 'model')) {
                return 'حدثت مشكلة في النموذج المستخدم. يرجى التواصل مع مسؤول النظام.';
            }

            // Log the full error for debugging
            Log::warning('OpenAI API Error Details', [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage()
            ]);

            return 'عذراً، حدث خطأ غير متوقع. إذا استمر الخطأ، يُرجى التواصل مع مسؤول النظام مع رسالة الخطأ التالية: ' . substr($e->getMessage(), 0, 50);
        }
    }

    /**
     * Calculate category-based and overall statistics for the user and date range.
     *
     * @param User $user
     * @param array $dateRange
     * @return array Calculated statistics (category details and overall totals)
     */
    private function calculateAggregatedStats(User $user, array $dateRange): array
    {
        $stats = [];
        $summary = [
            'income_total' => 0.0,
            'expense_total' => 0.0,
            'net_total' => 0.0
        ];

        // Calculate totals and averages per category
        $categoryStats = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('transactions.type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->select(
                'categories.name as category',
                'categories.type as category_type',
                'transactions.type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as total'),
                DB::raw('AVG(CASE WHEN transactions.try_equivalent IS NOT NULL THEN transactions.try_equivalent ELSE transactions.amount END) as average'),
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month")
            )
            ->groupBy('categories.name', 'categories.type', 'transactions.type', 'month')
            ->get();

        // Compute overall totals
        $incomeTotal = DB::table('transactions')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->where('type', TransactionTypeEnum::INCOME->value)
            ->sum(DB::raw('CASE WHEN try_equivalent IS NOT NULL THEN try_equivalent ELSE amount END'));
        
        $expenseTotal = DB::table('transactions')
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->where('type', TransactionTypeEnum::EXPENSE->value)
            ->sum(DB::raw('CASE WHEN try_equivalent IS NOT NULL THEN try_equivalent ELSE amount END'));

        $summary['income_total'] = (float) $incomeTotal;
        $summary['expense_total'] = abs((float) $expenseTotal);
        $summary['net_total'] = $summary['income_total'] - $summary['expense_total'];

        // Organize statistics
        foreach ($categoryStats->groupBy('category') as $category => $dataForCategory) {
            $categoryType = $dataForCategory->first()->category_type;
            $totalAmount = $dataForCategory->sum('total');
            $totalCount = $dataForCategory->sum('count');
                
            $stats[$categoryType][$category] = [
                'toplam' => $this->formatCurrency($totalAmount),
                'işlem_sayısı' => $totalCount,
                'ortalama' => $totalCount > 0 ? $this->formatCurrency($totalAmount / $totalCount) : $this->formatCurrency(0),
                'aylık_detay' => $dataForCategory->mapWithKeys(function ($data) {
                    return [
                        $data->month => [
                            'toplam' => $this->formatCurrency($data->total),
                            'işlem_sayısı' => $data->count
                        ]
                    ];
                })->toArray()
            ];
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
            return '0,00 TL';
        }
        return number_format(abs($amount), 2, ',', '.') . ' TL';
    }

    /**
     * Get the last N messages for the given conversation ID from the database
     * while trying to stay within a token budget.
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

        // Start with the last message and add messages, trying to stay within the token limit
        foreach ($allMessages as $message) {
            // Simple token estimation (for more accurate estimation, use a tokenizer)
            $contentLength = mb_strlen($message->content);
            $estimatedMessageTokens = (int)($contentLength * $tokenPerCharEstimate);
            
            // If the token limit will be exceeded, break the loop
            if ($estimatedTokens + $estimatedMessageTokens > $maxTokenEstimate) {
                break;
            }
            
            // Add the message to the result array (from old to new order)
            array_unshift($result, [
                'role' => $message->role === 'ai' ? 'assistant' : $message->role,
                'content' => $message->content
            ]);
            
            $estimatedTokens += $estimatedMessageTokens;
        }
        
        // At least add the last message (important context)
        if (empty($result) && !$allMessages->isEmpty()) {
            $lastMessage = $allMessages->first();
            $result[] = ['role' => $lastMessage->role === 'ai' ? 'assistant' : $lastMessage->role, 'content' => $lastMessage->content];
        }
        
        return $result;
    }

    /**
     * Get filtered transactions for the user and date range.
     *
     * @param User $user The user.
     * @param array $dateRange The date range.
     * @return \Illuminate\Support\Collection The filtered transactions.
     */
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
                'category' => $transaction->category ?? 'بدون فئة',
                'date' => $date->format('d.m.Y'),
                'description' => $this->maskSensitiveData($transaction->description)
            ];
        })->toArray();
    }

    /**
     * Build the prompt for OpenAI in message-array format.
     *
     * @param string $question
     * @param array $data Processed transaction data
     * @param array $stats Processed statistics (including summary)
     * @param array $dateRange
     * @param array $history Conversation history
     * @return array Message array for the OpenAI API
     */
    private function buildPrompt(
        string $question,
        array $data,
        array $stats,
        array $dateRange,
        array $history = []
    ): array 
    {
        $messages = [];

        // 1) System prompt
        $systemPrompt = config('ai.system_prompt') . "\n\nElinde olmayan veriyle ilgili sorularda, sadece mevcut veriler üzerinden analiz yap ve eksik olanı belirt. Eğer kullanıcı örneğin kredi kartı komisyonu gibi bir veri sorarsa ve bu veri yoksa, 'Bu konuda elimde veri yok, sadece mevcut işlemler üzerinden analiz yapabilirim.' şeklinde cevap ver.";
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        // 2) Conversation history
        if (!empty($history)) {
            foreach ($history as $message) {
                // Validate role and content
                if (!empty($message['role']) && !empty($message['content'])) {
                    $messages[] = $message;
                }
            }
        }

        // 3) Current question and context data
        $currentUserContent = "Kullanıcı Sorusu: {$question}\n\n"; // Highlight the question
        $currentUserContent .= "### Analiz Edilen Dönem و Özet Bilgiler\n"; // Markdown heading
        $currentUserContent .= "- Başlangıç Tarihi: " . Carbon::parse($dateRange['start'])->format('d.m.Y') . "\n";
        $currentUserContent .= "- Bitiş Tarihi: " . Carbon::parse($dateRange['end'])->format('d.m.Y') . "\n";
        $currentUserContent .= "- Dönem Tipi: " . $this->getPeriodTypeText($dateRange['period_type']) . "\n";
        $currentUserContent .= "- Toplam Gelir: " . ($stats['summary']['income_total'] ?? 'Hesaplanamadı') . "\n";
        $currentUserContent .= "- Toplam Gider: " . ($stats['summary']['expense_total'] ?? 'Hesaplanamadı') . "\n";
        $currentUserContent .= "- Net الحاله: " . ($stats['summary']['net_total'] ?? 'Hesaplanamadı') . "\n\n";

        $currentUserContent .= "### Önemli Notlar\n";
        $currentUserContent .= "1. Yanıtlarda Türk Lirası tutarlarını '.' binlik ayracı ve ',' ondalık ayracı ile formatla (Örnek: 1.234,56 TL).\n";
        $currentUserContent .= "2. Sadece soru sahibinin kendi verileri gösterilmektedir.\n";
        $currentUserContent .= "3. Transfer işlemleri analize dahil edilmemiştir.\n\n";
        
        // Present category stats in a separate section
        $categoryStatsForPrompt = $stats; // Copy original
        unset($categoryStatsForPrompt['summary']); // Remove summary
        $currentUserContent .= "### Kategori Bazlı İstatistikler\n" . json_encode($categoryStatsForPrompt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) . "\n\n"; // JSON_FORCE_OBJECT returns {} when empty
        
        // Present transactions in a separate section (e.g., limited)
        $currentUserContent .= "### Döneme Ait İşlemler (Örnekler)\n" . json_encode(array_slice($data, 0, 20), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // e.g., last 20

        $messages[] = ['role' => 'user', 'content' => $currentUserContent];

        return $messages;
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

    private function maskSensitiveData(?string $text): string
    {
        if (!$text) return '';
        
        $patterns = [
            '/\b\d{16}\b/' => '****-****-****-****',
            '/\bTR\d{24}\b/i' => 'TR**-****-****-****-****-****',
            // TC Kimlik No gibi hassas olabilecekleri de ekle:
            '/(?<!\d)\d{11}(?!\d)/' => '***********',
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
            // Cache for token usage
            $cacheKey = 'sql_query_' . md5($message . json_encode($databaseSchema));
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            // Convert schema to a readable format
            $schemaDescription = $this->formatSchemaForPrompt($databaseSchema);
            
            // Prepare system prompt
            $systemPrompt = <<<EOT
Sen bir veritabanı sorguları oluşturan AI asistanısın. Görevin, kullanıcının doğal dil sorgusunu analiz etmek ve eğer gerekiyorsa uygun bir SQL sorgusu oluşturmaktır. 
Sadece SELECT sorgularını oluşturabilirsin.

Şu veritabanı şeması hakkında bilgin var:
{$schemaDescription}

Kurallara uymalısın:
1. Kullanıcı finansal verileri, işlemleri, müşterileri, borçları vb. hakkında soru soruyorsa, uygun bir SQL sorgusu oluştur.
2. Kullanıcı genel site kullanımı, siteyle ilgili bilgi أو veritabanıyla ilgisi olmayan konular hakkında soru soruyorsa, SQL sorgusu OLUŞTURMA.
3. Kullanıcı veri değiştirme, ekleme, silme isteğinde bulunursa, SQL sorgusu OLUŞTURMA ve bunu yapamayacağını belirt.
4. SQL sorgularında sadece SELECT ifadelerini kullan, hiçbir şekilde INSERT, UPDATE, DELETE, DROP أو diğer veri değiştiren ifadeleri kullanma.
5. İlgili tablolar arasında JOIN yaparak ilişkili verileri sorgulayabilirsin.
6. Tablo ve sütun isimlerini tam olarak veritabanı şemasında belirtildiği gibi kullan.
7. Oluşturduğun sorgu MySQL'de çalışacak şekilde olmalıdır.

Yanıtın şu formatta olmalıdır:
{
  "requires_sql": boolean,  // SQL sorgusu gerekli mi?
  "query": string,          // SQL sorgusu (requires_sql true ise)
  "explanation": string     // Sorgunu أو SQL sorgusu oluşturmama nedenini açıkla
}
EOT;

            // Prepare messages
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ];
            
            // Send request to API
            $response = $this->openai->chat()->create([
                'model' => config('ai.openai.model'),
                'messages' => $messages,
                'temperature' => 0.2, // Lower temperature for more deterministic results
                'response_format' => ['type' => 'json_object'] // Request JSON response
            ]);
            
            // Parse JSON response
            $content = $response->choices[0]->message->content;
            $result = json_decode($content, true);
            
            // Fill missing fields if needed
            if (!isset($result['requires_sql'])) {
                $result['requires_sql'] = false;
            }
            
            if (!isset($result['query'])) {
                $result['query'] = '';
            }
            
            if (!isset($result['explanation'])) {
                $result['explanation'] = 'Analiz sonucu bulunamadı.';
            }
            
            // Cache result (2 hours)
            \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 7200);
            
            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SQL query generation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message
            ]);
            
            // Default response on error
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
     * @param string $conversationId The conversation ID.
     * @return string
     */
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, string $conversationId = null): string
    {
        try {
            // Cache for token usage
            $cacheKey = 'sql_answer_' . md5($message . $sqlQuery . json_encode($sqlResults) . ($conversationId ?? ''));
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }
            
            // Convert results to a readable format
            $resultsText = $this->formatSqlResultsForPrompt($sqlResults);
            
            // Prepare system prompt
            $systemPrompt = <<<EOT
Sen bir finansal veri analistine yardımcı olan AI asistanısın. Kullanıcının sorusuna yanıt vermek için bir SQL sorgusu çalıştırıldı.
Görevin, SQL sorgu sonuçlarını inceleyerek ve kullanıcının orijinal sorusunu dikkate alarak anlamlı, açıklayıcı bir yanıt oluşturmaktır.

Yanıtın şunları içermelidir:
1. Sorunun doğrudan yanıtı
2. Varsa önemli eğilimler, desenler أو gözlemler
3. Gerekiyorsa sorgu sonuçlarından çıkarılan önemli bilgiler
4. Türkçe ve anlaşılır bir dil kullan

Kullanıcıya çıplak SQL sorgusu أو teknik jargon gösterme. Sonuçları anlamlı bir analize dönüştür.

Mevcut konuşma geçmişini ve kullanıcının ilgi alanlarını dikkate al. Yanıtın uzunluğu sorunun karmaşıklığına uygun olmalıdır.
EOT;

            // Prepare messages
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
                ['role' => 'assistant', 'content' => "Sorunuzu anlıyorum. Bu bilgiyi almak için bir veritabanı sorgusu çalıştırdım."],
                ['role' => 'user', 'content' => "Çalıştırılan SQL sorgusu:\n```sql\n{$sqlQuery}\n```\n\nSorgu sonuçları:\n```\n{$resultsText}\n```\n\nBu sonuçları analiz ederek bana anlamlı bir yanıt ver. Kullanıcı dostu bir dille açıkla ve önemli noktaları vurgula."]
            ];
            
            // Send request to API
            $response = $this->openai->chat()->create([
                'model' => config('ai.openai.model'),
                'messages' => $messages,
                'temperature' => 0.7, // Higher temperature for more creative explanations
            ]);
            
            $content = $response->choices[0]->message->content;
            
            // Cache result (2 hours)
            \Illuminate\Support\Facades\Cache::put($cacheKey, $content, 7200);
            
            return $content;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SQL results analysis error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message,
                'sql_query' => $sqlQuery
            ]);
            
            // Default response on error
            return "Üzgünüm, veritabanı sonuçlarını analiz ederken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
    
    /**
     * Format the database schema for the prompt.
     */
    protected function formatSchemaForPrompt(array $schema): string
    {
        $output = "Tablolar ve alanlar:\n\n";
        
        foreach ($schema['tables'] as $tableName => $columns) {
            $output .= "- {$tableName}\n";
            
            foreach ($columns as $columnName => $columnType) {
                $output .= "  - {$columnName}: {$columnType}\n";
            }
            
            $output .= "\n";
        }
        
        $output .= "İlişkiler:\n\n";
        
        foreach ($schema['relationships'] as $relation) {
            $output .= "- {$relation['source_table']}.{$relation['source_column']} -> {$relation['target_table']}.{$relation['target_column']} ({$relation['type']})\n";
        }
        
        return $output;
    }
    
    /**
     * SQL sonuçlarını prompt için formatla
     */
    protected function formatSqlResultsForPrompt(array $results): string
    {
        if (empty($results)) {
            return "Sonuç bulunamadı.";
        }
        
        $output = "";
        
        // First 20 rows (or less)
        $limit = min(count($results), 20);
        
        for ($i = 0; $i < $limit; $i++) {
            $row = $results[$i];
            $output .= "Satır " . ($i + 1) . ":\n";
            
            foreach ($row as $key => $value) {
                // Special handling for null values
                if ($value === null) {
                    $value = "NULL";
                }
                // For arrays and objects
                elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                $output .= "  {$key}: {$value}\n";
            }
            
            $output .= "\n";
        }
        
        // If there are more results, indicate that
        if (count($results) > $limit) {
            $remaining = count($results) - $limit;
            $output .= "... ve {$remaining} satır daha (toplam " . count($results) . " satır)";
        } else {
            $output .= "Toplam " . count($results) . " satır.";
        }
        
        return $output;
    }
}