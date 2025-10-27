<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\Services\AI\Exceptions\UnsafeSqlException;

class SqlQueryService
{
    /**
     * Allowed SQL commands
     */
    protected const ALLOWED_COMMANDS = ['SELECT'];
    
    /**
     * Forbidden SQL keywords
     */
    protected const FORBIDDEN_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 
        'CREATE', 'RENAME', 'EXEC', 'EXECUTE', 'STORED PROCEDURE',
        'GRANT', 'REVOKE', 'COMMIT', 'ROLLBACK', 'SAVEPOINT', 'MERGE',
        'CALL', 'CONNECT', 'LOCK', 'PREPARE', 'DEALLOCATE', 'SET',
        'EXPLAIN', 'ANALYZE'
    ];
    
    /**
     * Validate the SQL query.
     * 
     * @param string $query
     * @return bool
     * @throws UnsafeSqlException
     */
    public function validateQuery(string $query): bool
    {
        $normalizedQuery = strtoupper(trim($query));
        
        // Only allow SELECT operations
        $hasAllowedStart = false;
        foreach (self::ALLOWED_COMMANDS as $command) {
            if (strpos($normalizedQuery, $command) === 0) {
                $hasAllowedStart = true;
                break;
            }
        }
        
        if (!$hasAllowedStart) {
            throw new UnsafeSqlException("SQL sorgusu izin verilen bir komutla başlamalıdır.");
        }
        
        // Check for forbidden keywords
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword) . '\b/i', $normalizedQuery)) {
                throw new UnsafeSqlException("SQL sorgusunda güvenli olmayan anahtar kelime bulundu: {$keyword}");
            }
        }
        
        // Multiple query check - only allow one query
        if (strpos($normalizedQuery, ';') !== false) {
            // Special case: if the last character is a semicolon, allow it
            $lastChar = substr(trim($normalizedQuery), -1);
            if ($lastChar === ';') {
                // No problem, last character is a semicolon
            } else if (substr_count($normalizedQuery, ';') > 1) {
                throw new UnsafeSqlException("Birden fazla SQL sorgusu çalıştırılamaz.");
            }
        }
        
        return true;
    }
    
    /**
     * Execute the SQL query.
     * 
     * @param string $query
     * @return array
     * @throws UnsafeSqlException
     * @throws QueryException
     */
    public function executeQuery(string $query): array
    {
        $this->validateQuery($query);
        
        try {
            Log::info('Executing SQL query', ['query' => $query]);
            
            // Additional security measures can be added here if needed
            $results = DB::select($query);
            
            // Convert results to indexed array
            $results = json_decode(json_encode($results), true);
            
            Log::info('SQL query executed successfully', [
                'query' => $query,
                'results_count' => count($results)
            ]);
            
            return $results;
        } catch (QueryException $e) {
            Log::error('SQL query execution error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            throw $e;
        }
    }
} 