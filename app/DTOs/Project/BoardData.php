<?php

declare(strict_types=1);

namespace App\DTOs\Project;

/**
 * Proje Board Data Transfer Object
 * 
 * Used to transfer and convert project board data.
 * Used for project board creation and viewing.
 */
class BoardData
{
    /**
     * @param string $name Board name
     * @param int $project_id Project ID
     */
    public function __construct(
        public readonly string $name,
        public readonly int $project_id,
    ) {}

    /**
     * Create board data from array
     * 
     * @param array $data Board data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            project_id: $data['project_id'],
        );
    }

    /**
     * Convert board data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'project_id' => $this->project_id,
        ];
    }
} 