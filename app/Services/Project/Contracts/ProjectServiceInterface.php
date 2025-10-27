<?php

declare(strict_types=1);

namespace App\Services\Project\Contracts;

use App\Models\Project;
use App\Models\Board;
use App\Models\Task;
use App\Models\TaskList;
use App\DTOs\Project\ProjectData;
use App\DTOs\Project\BoardData;

/**
 * Project service interface
 * 
 * Defines the methods required for managing project operations.
 * Handles project, board, task list, and task management.
 */
interface ProjectServiceInterface
{
    /**
     * Create a new project.
     * 
     * @param ProjectData $data Project data
     * @return Project Created project
     */
    public function create(ProjectData $data): Project;

    /**
     * Update an existing project.
     * 
     * @param Project $project Project to update
     * @param ProjectData $data New project data
     * @return Project Updated project
     */
    public function update(Project $project, ProjectData $data): Project;

    /**
     * Delete a project.
     * 
     * @param Project $project Project to delete
     */
    public function delete(Project $project): void;

    /**
     * Create a new board.
     * 
     * @param BoardData $data Board data
     * @return Board Created board
     */
    public function createBoard(BoardData $data): Board;

    /**
     * Get the default board of a project or create it if it doesn't exist.
     * 
     * @param Project $project Project
     * @return Board Default board
     */
    public function getOrCreateDefaultBoard(Project $project): Board;

    /**
     * Create a new task list.
     * 
     * @param Board $board Board
     * @param array $data Task list data
     * @return TaskList Created task list
     */
    public function createTaskList(Board $board, array $data): TaskList;

    /**
     * Update an existing task list.
     * 
     * @param TaskList $list Task list to update
     * @param array $data New task list data
     * @return TaskList Updated task list
     */
    public function updateTaskList(TaskList $list, array $data): TaskList;

    /**
     * Update the order of task lists.
     * 
     * @param array $lists Task lists to update
     */
    public function reorderTaskLists(array $lists): void;

    /**
     * Create a new task.
     * 
     * @param TaskList $list Task list
     * @param array $data Task data
     * @return Task Created task
     */
    public function createTask(TaskList $list, array $data): Task;

    /**
     * Update an existing task.
     * 
     * @param Task $task Task to update
     * @param array $data New task data
     * @return Task Updated task
     */
    public function updateTask(Task $task, array $data): Task;

    /**
     * Delete a task.
     * 
     * @param Task $task Task to delete
     */
    public function deleteTask(Task $task): void;

    /**
     * Update the order of tasks.
     * 
     * @param array $tasks Tasks to update
     * @param string|null $targetListId Target list ID
     */
    public function reorderTasks(array $tasks, ?string $targetListId = null): void;
} 