<?php

declare(strict_types=1);

namespace App\DTOs\Project;

/**
 * Project Data Transfer Object
 * 
 * Used to transfer and convert project data.
 * Used for project creation, updating, and viewing.
 */
class ProjectData
{
    /**
     * @param string $name Project name
     * @param string|null $description Project description
     * @param string $status Project status
     * @param int|null $created_by Created by user ID
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?int $created_by = null,
    ) {}

    /**
     * Create project data from array
     * 
     * @param array $data Project data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'],
            created_by: $data['created_by'] ?? null,
        );
    }

    /**
     * Convert project data to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
        ];
    }
} 