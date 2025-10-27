<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseSchemaService
{
    /**
     * Cache key
     */
    protected const CACHE_KEY = 'database_schema_for_ai';
    
    /**
     * Cache TTL in seconds - 24 hours
     */
    protected const CACHE_TTL = 86400;
    
    /**
     * Path to schema file
     */
    protected $schemaFilePath;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->schemaFilePath = base_path('docs/database.md');
    }
    
    /**
     * Get database schema (from cache or file)
     * 
     * @return array
     */
    public function getSchema(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->parseSchemaFromFile();
        });
    }
    
    /**
     * Parse database schema from file
     * 
     * @return array
     */
    protected function parseSchemaFromFile(): array
    {
        try {
            if (!File::exists($this->schemaFilePath)) {
                Log::info('Database schema file not found, fetching from database', [
                    'path' => $this->schemaFilePath
                ]);
                return $this->getSchemaFromDatabase();
            }
            
            $content = File::get($this->schemaFilePath);
            
            $schema = [
                'tables' => [],
                'relationships' => []
            ];
            
            // Extract table sections
            preg_match_all('/### ([a-z_]+)\s*\n(- .+\n)+/', $content, $tableMatches);
            
            foreach ($tableMatches[0] as $index => $tableSection) {
                $tableName = $tableMatches[1][$index];
                
                // Extract columns
                preg_match_all('/- ([a-z_]+) \(([^)]+)\)/', $tableSection, $columnMatches);
                
                $columns = [];
                foreach ($columnMatches[1] as $colIndex => $columnName) {
                    $columnType = $columnMatches[2][$colIndex];
                    $columns[$columnName] = $columnType;
                    
                    // Detect relationships (foreign key)
                    if (strpos($columnType, 'foreign key') !== false) {
                        // Infer target table
                        $targetTable = str_replace('_id', '', $columnName);
                        
                        $schema['relationships'][] = [
                            'source_table' => $tableName,
                            'source_column' => $columnName,
                            'target_table' => $targetTable,
                            'target_column' => 'id',
                            'type' => 'belongs_to'
                        ];
                    }
                }
                
                $schema['tables'][$tableName] = $columns;
            }
            
            Log::info('Database schema parsed successfully', [
                'tables_count' => count($schema['tables']),
                'relationships_count' => count($schema['relationships'])
            ]);
            
            return $schema;
        } catch (\Exception $e) {
            Log::error('Error parsing database schema', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getSchemaFromDatabase();
        }
    }
    
    /**
     * Get schema from database directly
     * 
     * @return array
     */
    protected function getSchemaFromDatabase(): array
    {
        try {
            $schema = [
                'tables' => [],
                'relationships' => []
            ];
            
            // Get all tables
            $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            $databaseName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            
            foreach ($tables as $table) {
                $tableName = $table->{"Tables_in_$databaseName"};
                
                // Skip migrations and cache tables
                if (str_contains($tableName, 'migrations') || str_contains($tableName, 'cache')) {
                    continue;
                }
                
                // Get columns for this table
                $columns = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM `$tableName`");
                
                $columnsArray = [];
                foreach ($columns as $column) {
                    $columnsArray[$column->Field] = $column->Type;
                    
                    // Detect foreign keys
                    if (str_ends_with($column->Field, '_id')) {
                        $targetTable = str_replace('_id', '', $column->Field);
                        $schema['relationships'][] = [
                            'source_table' => $tableName,
                            'source_column' => $column->Field,
                            'target_table' => $targetTable,
                            'target_column' => 'id',
                            'type' => 'belongs_to'
                        ];
                    }
                }
                
                $schema['tables'][$tableName] = $columnsArray;
            }
            
            Log::info('Database schema fetched from database', [
                'tables_count' => count($schema['tables']),
                'relationships_count' => count($schema['relationships'])
            ]);
            
            return $schema;
        } catch (\Exception $e) {
            Log::error('Error fetching database schema', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'tables' => [],
                'relationships' => []
            ];
        }
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
} 