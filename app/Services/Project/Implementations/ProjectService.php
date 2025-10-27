<?php

declare(strict_types=1);

namespace App\Services\Project\Implementations;

use App\Models\Project;
use App\Models\Board;
use App\Models\Task;
use App\Models\TaskList;
use App\Services\Project\Contracts\ProjectServiceInterface;
use App\DTOs\Project\ProjectData;
use App\DTOs\Project\BoardData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Project service implementation
 * 
 * Contains methods required to manage project operations.
 * Handles creating, updating, and deleting projects, boards, task lists, and tasks.
 */
final class ProjectService implements ProjectServiceInterface
{
    /**
     * Create a new project.
     * 
     * @param ProjectData $data Project data
     * @return Project Created project
     */
    public function create(ProjectData $data): Project
    {
        return DB::transaction(function () use ($data) {
            return Project::create($data->toArray());
        });
    }

    /**
     * Update an existing project.
     * 
     * @param Project $project Project to update
     * @param ProjectData $data New project data
     * @return Project Updated project
     */
    public function update(Project $project, ProjectData $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->update([
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
            ]);
            return $project->fresh();
        });
    }

    /**
     * Delete a project.
     * 
     * @param Project $project Project to delete
     */
    public function delete(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project->delete();
        });
    }

    /**
     * Create a new board.
     * 
     * @param BoardData $data Board data
     * @return Board Created board
     */
    public function createBoard(BoardData $data): Board
    {
        return DB::transaction(function () use ($data) {
            return Board::create($data->toArray());
        });
    }

    /**
     * Get the default board of a project or create it if it doesn't exist.
     * 
     * @param Project $project Project
     * @return Board Default board
     */
    public function getOrCreateDefaultBoard(Project $project): Board
    {
        return $project->boards()->firstOrCreate(
            [],
            ['name' => 'Ana Board']
        );
    }

    /**
     * Create a new task list.
     * 
     * @param Board $board Board
     * @param array $data Task list data
     * @return TaskList Created task list
     */
    public function createTaskList(Board $board, array $data): TaskList
    {
        return DB::transaction(function () use ($board, $data) {
            $maxOrder = $board->taskLists()->max('order') ?? 0;
            
            return $board->taskLists()->create([
                'name' => $data['name'],
                'order' => $maxOrder + 1,
            ]);
        });
    }

    /**
     * Update an existing task list.
     * 
     * @param TaskList $list Task list to update
     * @param array $data New task list data
     * @return TaskList Updated task list
     */
    public function updateTaskList(TaskList $list, array $data): TaskList
    {
        return DB::transaction(function () use ($list, $data) {
            $list->update([
                'name' => $data['name'],
            ]);
            return $list->fresh();
        });
    }

    /**
     * Update the order of task lists.
     * 
     * @param array $lists Task lists to update
     */
    public function reorderTaskLists(array $lists): void
    {
        if (empty($lists)) return;

        DB::transaction(function () use ($lists) {
            foreach ($lists as $index => $listData) {
                $list = TaskList::find($listData['id']);
                if (!$list) continue;

                $list->update(['order' => $index]);
            }
        });
    }

    /**
     * Create a new task.
     * 
     * @param TaskList $list Task list
     * @param array $data Task data
     * @return Task Created task
     */
    public function createTask(TaskList $list, array $data): Task
    {
        return DB::transaction(function () use ($list, $data) {
            $maxOrder = $list->tasks()->max('order') ?? 0;
            
            return $list->tasks()->create([
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'],
                'checklist' => $data['checklist'] ?? [],
                'assigned_to' => !empty($data['assigned_to']) ? $data['assigned_to'] : null,
                'order' => $maxOrder + 1,
                'task_list_id' => $list->id
            ]);
        });
    }

    /**
     * Update an existing task.
     * 
     * @param Task $task Task to update
     * @param array $data New task data
     * @return Task Updated task
     */
    public function updateTask(Task $task, array $data): Task
    {
        $task->update([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'checklist' => $data['checklist'] ?? [],
            'assigned_to' => !empty($data['assigned_to']) ? $data['assigned_to'] : null
        ]);

        return $task;
    }

    /**
     * Delete a task.
     * 
     * @param Task $task Task to delete
     */
    public function deleteTask(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $task->delete();
        });
    }

    /**
     * Update the order of tasks.
     * 
     * @param array $tasks Tasks to update
     * @param string|null $targetListId Target list ID
     */
    public function reorderTasks(array $tasks, ?string $targetListId = null): void
    {
        if (empty($tasks)) return;

        DB::transaction(function () use ($tasks, $targetListId) {
            foreach ($tasks as $index => $taskData) {
                $task = Task::find($taskData['id']);
                if (!$task) continue;

                $task->update([
                    'order' => $index,
                    'task_list_id' => $targetListId ?? $task->task_list_id
                ]);
            }
        });
    }
} 