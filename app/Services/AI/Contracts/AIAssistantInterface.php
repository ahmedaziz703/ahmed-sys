<?php

namespace App\Services\AI\Contracts;

use App\Models\User;

interface AIAssistantInterface
{
    /**
     * Send a user's question to the AI model and receive the answer.
     *
     * @param User $user The user asking the question.
     * @param string $question The user's question.
     * @param string|null $conversationId The ID of the current conversation (if any).
     * @return string The answer from the AI model.
     */
    public function query(User $user, string $question, ?string $conversationId = null): string;
    
    /**
     * Analyze a user's message and generate an SQL query.
     * 
     * @param mixed $user The user object.
     * @param string $message The user's message.
     * @param array $databaseSchema The database schema (table and field information).
     * @return array ['query' => string, 'requires_sql' => bool, 'explanation' => string]
     */
    public function generateSqlQuery($user, string $message, array $databaseSchema): array;
    
    /**
     * Create a response using SQL results.
     * 
     * @param mixed $user The user object.
     * @param string $message The user's message.
     * @param string $sqlQuery The executed SQL query.
     * @param array $sqlResults The SQL results.
     * @param string $conversationId The conversation ID.
     * @return string
     */
    public function queryWithSqlResults($user, string $message, string $sqlQuery, array $sqlResults, string $conversationId = null): string;
} 