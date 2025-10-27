<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

/**
 * Customer Note Data Transfer Object
 * 
 * Used to transfer and convert customer note data.
 * Used for customer note creation and viewing.
 */
class NoteData
{
    /**
     * @param int $customer_id Customer ID
     * @param string $type Note type
     * @param string $content Note content
     * @param string $activity_date Activity date
     * @param int|null $user_id User ID
     * @param int|null $assigned_user_id Assigned user ID
     */
    public function __construct(
        public readonly int $customer_id,
        public readonly string $type,
        public readonly string $content,
        public readonly string $activity_date,
        public readonly ?int $user_id = null,
        public readonly ?int $assigned_user_id = null,
    ) {}

    /**
     * Create note data from array
     * 
     * @param array $data Note data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: (int) $data['customer_id'],
            type: $data['type'],
            content: $data['content'],
            activity_date: $data['activity_date'],
            user_id: $data['user_id'] ? (int) $data['user_id'] : null,
            assigned_user_id: $data['assigned_user_id'] ? (int) $data['assigned_user_id'] : null,
        );
    }

    /**
     * Convert note data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'content' => $this->content,
            'activity_date' => $this->activity_date,
            'user_id' => $this->user_id,
            'assigned_user_id' => $this->assigned_user_id,
        ];
    }
} 