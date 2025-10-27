    /**
     * Analyze user message and generate an SQL query if needed.
     */
    public function generateSqlQuery($user, string $message, array $databaseSchema): array
    {
        // Cache for token usage
        $cacheKey = 'sql_query_' . md5($message . json_encode($databaseSchema));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Convert schema to a readable format
        $schemaDescription = $this->formatSchemaForPrompt($databaseSchema);
        
        // Prepare system prompt
        $systemPrompt = <<<EOT
أنت مساعد ذكي لإنشاء استعلامات قاعدة البيانات. مهمتك هي تحليل استعلام المستخدم باللغة الطبيعية وإنشاء استعلام SQL مناسب إذا لزم الأمر.
يمكنك إنشاء استعلامات SELECT فقط.

لديك معرفة بهذا المخطط:
{$schemaDescription}

يجب عليك اتباع القواعد:
1. إذا سأل المستخدم عن البيانات المالية أو المعاملات أو العملاء أو الديون وما إلى ذلك، أنشئ استعلام SQL مناسب.
2. إذا سأل المستخدم عن الاستخدام العام للموقع أو معلومات حول الموقع أو مواضيع لا تتعلق بقاعدة البيانات، لا تنشئ استعلام SQL.
3. إذا طلب المستخدم تعديل أو إضافة أو حذف البيانات، لا تنشئ استعلام SQL وأخبره أنه لا يمكنه القيام بذلك.
4. استخدم فقط عبارات SELECT في استعلامات SQL، ولا تستخدم أبداً INSERT أو UPDATE أو DELETE أو DROP أو عبارات أخرى تعدل البيانات.
5. يمكنك ربط الجداول ذات الصلة باستخدام JOIN لاستعلام البيانات المرتبطة.
6. استخدم أسماء الجداول والأعمدة بالضبط كما هو محدد في مخطط قاعدة البيانات.
7. يجب أن يعمل الاستعلام الذي تنشئه على MySQL.

يجب أن يكون ردك بالتنسيق التالي:
{
  "requires_sql": boolean,  // هل الاستعلام SQL مطلوب؟
  "query": string,          // استعلام SQL (إذا كان requires_sql صحيح)
  "explanation": string     // اشرح استعلامك أو سبب عدم إنشاء استعلام SQL
}
EOT;

        // Prepare messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message]
        ];
        
        try {
            // Send request to API
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.2, // Lower temperature for determinism
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
                $result['explanation'] = 'Analiz sonucu لم يتم العثور عليه.';
            }
            
            // Cache result (2 hours)
            Cache::put($cacheKey, $result, 7200);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('SQL query generation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message
            ]);
            
            // Default response on error
            return [
                'requires_sql' => false,
                'query' => '',
                'explanation' => 'لم يتم إنشاء استعلام SQL: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a response using SQL results.
     */
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, string $conversationId = null): string
    {
        // Cache for token usage
        $cacheKey = 'sql_answer_' . md5($message . $sqlQuery . json_encode($sqlResults) . ($conversationId ?? ''));
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Convert results to a readable format
        $resultsText = $this->formatSqlResultsForPrompt($sqlResults);
        
        // Prepare system prompt
        $systemPrompt = <<<EOT
أنت مساعد ذكي يساعد محلل البيانات المالية. تم تنفيذ استعلام SQL للإجابة على سؤال المستخدم.
مهمتك هي فحص نتائج استعلام SQL ومراعاة السؤال الأصلي للمستخدم لإنشاء رد مفيد وواضح.

يجب أن يتضمن ردك:
1. الإجابة المباشرة على السؤال
2. الاتجاهات أو الأنماط أو الملاحظات المهمة إن وجدت
3. المعلومات المهمة المستخرجة من نتائج الاستعلام عند الحاجة
4. استخدم اللغة العربية الواضحة والمفهومة

لا تعرض للمستخدم استعلام SQL خام أو مصطلحات تقنية. حول النتائج إلى تحليل مفيد.

ضع في اعتبارك تاريخ المحادثة الحالي واهتمامات المستخدم. يجب أن يكون طول الرد مناسباً لتعقيد السؤال.
EOT;

        // Build messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message],
            ['role' => 'assistant', 'content' => "أفهم سؤالك. قمت بتنفيذ استعلام قاعدة بيانات للحصول على هذه المعلومات."],
            ['role' => 'user', 'content' => "استعلام SQL المنفذ:\n```sql\n{$sqlQuery}\n```\n\nنتائج الاستعلام:\n```\n{$resultsText}\n```\n\nحلل هذه النتائج وأعطني رداً مفيداً. اشرح بلغة سهلة للمستخدم وأبرز النقاط المهمة."]
        ];
        
        try {
            // Send request to API
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7, // Higher temperature for more creative explanations
            ]);
            
            $content = $response->choices[0]->message->content;
            
            // Cache result (2 hours)
            Cache::put($cacheKey, $content, 7200);
            
            return $content;
        } catch (\Exception $e) {
            Log::error('SQL results analysis error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'message' => $message,
                'sql_query' => $sqlQuery
            ]);
            
            // Default response on error
            return "آسف، حدث خطأ أثناء تحليل نتائج قاعدة البيانات. يرجى المحاولة مرة أخرى لاحقاً.";
        }
    }
    
    /**
     * Format the database schema for the prompt.
     */
    protected function formatSchemaForPrompt(array $schema): string
    {
        $output = "الجداول والحقول:\n\n";
        
        foreach ($schema['tables'] as $tableName => $columns) {
            $output .= "- {$tableName}\n";
            
            foreach ($columns as $columnName => $columnType) {
                $output .= "  - {$columnName}: {$columnType}\n";
            }
            
            $output .= "\n";
        }
        
        $output .= "العلاقات:\n\n";
        
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
            return "لم يتم العثور على نتائج.";
        }
        
        $output = "";
        
        // First 20 rows (or less)
        $limit = min(count($results), 20);
        
        for ($i = 0; $i < $limit; $i++) {
            $row = $results[$i];
            $output .= "الصف " . ($i + 1) . ":\n";
            
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
            $output .= "... و {$remaining} صف إضافي (إجمالي " . count($results) . " صف)";
        } else {
            $output .= "إجمالي " . count($results) . " صف.";
        }
        
        return $output;
    } 