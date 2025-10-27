<?php

namespace App\Livewire\Project\Board;

use App\Models\Board;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use App\Services\Project\Contracts\ProjectServiceInterface;
use Livewire\Component;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Exceptions\Halt;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\ActionGroup;
use Filament\Actions\StaticAction;
use Illuminate\Support\Facades\DB;

/**
 * Project Board Manager Component
 * 
 * This component provides functionality to manage project boards.
 * Features:
 * - Task list creation and editing
 * - Task creation and editing
 * - Task addition, editing and deletion    
 * - Task reordering and movement
 * - Task assignment and labeling
 * - Task checklist management
 * - Kanban view
 * 
 * @package App\Livewire\Project\Board
 */
class BoardManager extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var Project Project */
    public Project $project;

    /** @var Board|null Active board */
    public ?Board $board = null;

    /** @var int|null Editing task list ID */
    public ?int $editingTaskListId = null;

    /** @var Task|null Editing task */
    public ?Task $editingTask = null;

    /** @var array Tasks */
    public array $tasks = [];

    /** @var array Users */
    public $users = [];

    /** @var bool List modal visibility */
    public bool $showListModal = false;

    /** @var bool Task modal visibility */
    public bool $showTaskModal = false;

    /** @var bool Delete modal visibility */
    public bool $showDeleteModal = false;

    /** @var bool List edit modal visibility */
    public bool $showEditListModal = false;

    /** @var array List form data */
    public array $listData = [];

    /** @var array Task form data */
    public array $taskData = [];

    /** @var int|null Deleting task ID */
    public ?int $deletingTaskId = null;

    /** @var TaskList|null Editing list */
    public ?TaskList $editingList = null;

    /** @var ProjectServiceInterface Project service */
    private ProjectServiceInterface $projectService;

    /** @var bool Kanban view refresh status */
    public bool $shouldRenderKanban = true;

    /**
     * When the component is booted, the project service is injected
     * 
     * @param ProjectServiceInterface $projectService Project service
     * @return void
     */
    public function boot(ProjectServiceInterface $projectService): void 
    {
        $this->projectService = $projectService;
    }

    /**
     * When the component is booted, the project and board data are loaded
     * 
     * @param Project $project Project
     * @return void
     */
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->board = $this->projectService->getOrCreateDefaultBoard($project);
        
        foreach ($this->board->taskLists as $list) {
            $this->tasks[$list->id] = $list->tasks->sortBy('order')->values();
        }

        // Active users get
        $this->users = User::orderBy('name')->get();
    }

    /**
     * Opens the new list creation modal
     * 
     * @return void
     */
    public function addList(): void
    {
        // When adding a list, clear the state
        $this->reset('listData');
        $this->showListModal = true;
    }

    /**
     * Creates a new list
     * 
     * @return void
     */
    public function createList(): void
    {
        $this->validate([
            'listData.name' => 'required|string|max:255',
        ]);

        $this->projectService->createTaskList($this->board, $this->listData);

        $this->listData = [];
        $this->showListModal = false;

        Notification::make()
            ->success()
            ->title('Liste eklendi')
            ->send();

        // Refresh the page
        $this->redirect(request()->header('Referer'));
    }

    /**
     * Opens the new task creation modal
     * 
     * @param int $listId List ID
     * @return void
     */
    public function addTask(int $listId): void
    {
        // When adding a task, clear the state
        $this->reset(['editingTask', 'taskData']);
        
        $this->editingTaskListId = $listId;
        $this->taskData = [
            'title' => '',
            'content' => '',
            'priority' => 'medium',
            'due_date' => null,
            'checklist' => [],
            'assigned_to' => null
        ];
        $this->showTaskModal = true;
    }

    /**
     * Opens the task editing modal
     * 
     * @param int $taskId Task ID
     * @return void
     */
    public function editTask(int $taskId): void
    {
        $this->editingTask = Task::find($taskId);
        $this->editingTaskListId = $this->editingTask->task_list_id;
        
        $this->taskData = [
            'title' => $this->editingTask->title,
            'content' => $this->editingTask->content ?? '',
            'priority' => $this->editingTask->priority,
            'due_date' => $this->editingTask->due_date?->format('Y-m-d'),
            'checklist' => $this->editingTask->checklist ?? [],
            'assigned_to' => $this->editingTask->assigned_to ?: null
        ];
        
        $this->showTaskModal = true;
    }

    /**
     * Creates or updates a task
     * 
     * @return void
     */
    public function createTask(): void
    {
        $this->validate([
            'taskData.title' => 'required|string|max:255',
            'taskData.content' => 'nullable|string|max:2000',
            'taskData.priority' => 'required|in:low,medium,high',
            'taskData.due_date' => 'nullable|date',
            'taskData.checklist' => 'nullable|array',
            'taskData.assigned_to' => 'nullable|exists:users,id'
        ]);

        $list = TaskList::findOrFail($this->editingTaskListId);

        if ($this->editingTask) {
            $this->projectService->updateTask($this->editingTask, $this->taskData);
            $message = 'Görev güncellendi';
        } else {
            $this->projectService->createTask($list, $this->taskData);
            $message = 'تم الانشاء بنجاح';
        }

        $this->reset(['taskData', 'showTaskModal', 'editingTask', 'editingTaskListId']);

        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }

    /**
     * Opens the task deletion modal
     * 
     * @param int $taskId Task ID
     * @return void
     */
    public function confirmTaskDeletion(int $taskId): void
    {
        $this->deletingTaskId = $taskId;
        $this->showDeleteModal = true;
    }

    /**
     * Deletes a task
     * 
     * @return void
     */
    public function deleteTask(): void
    {
        $task = Task::findOrFail($this->deletingTaskId);
        $this->projectService->deleteTask($task);

        $this->showDeleteModal = false;
        $this->deletingTaskId = null;

        Notification::make()
            ->success()
            ->title('Görev silindi')
            ->send();
    }

    /**
     * Opens the list editing modal
     * 
     * @param TaskList $list List
     * @return void
     */
    public function editList(TaskList $list): void
    {
        // When editing a list, clear the state
        $this->reset('listData');
        
        $this->editingList = $list;
        $this->listData = [
            'name' => $list->name,
        ];
        $this->showEditListModal = true;
    }

    /**
     * Updates a list
     * 
     * @return void
     */
    public function updateList(): void
    {
        $this->validate([
            'listData.name' => 'required|string|max:255',
        ]);

        $this->projectService->updateTaskList($this->editingList, $this->listData);

        $this->reset(['listData', 'showEditListModal', 'editingList']);

        Notification::make()
            ->success()
            ->title('Liste güncellendi')
            ->send();
    }

    /**
     * Updates the task order
     * 
     * @param array $items Order data
     * @param int|null $sourceListId Source list ID
     * @param int|null $targetListId Target list ID
     * @return void
     */
    public function updateTaskOrder($items, $sourceListId = null, $targetListId = null): void
    {
        if (!$items) return;

        $this->projectService->reorderTasks($items, $targetListId ?? $sourceListId);
    }

    /**
     * Updates the list order
     * 
     * @param array $items Order data
     * @return void
     */
    public function updateListOrder($items): void
    {
        if (!$items) return;
        $this->projectService->reorderTaskLists($items);
    }

    /**
     * Closes the task modal
     * 
     * @return void
     */
    public function closeTaskModal(): void
    {
        $this->reset(['taskData', 'showTaskModal', 'editingTask', 'editingTaskListId']);
    }

    /**
     * Closes the list modal
     * 
     * @return void
     */
    public function closeListModal(): void
    {
        $this->reset(['listData', 'showListModal']);
    }

    /**
     * Closes the list edit modal
     * 
     * @return void
     */
    public function closeEditListModal(): void
    {
        $this->reset(['listData', 'showEditListModal', 'editingList']);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $lists = $this->board->taskLists()
            ->with(['tasks' => function($query) {
                $query->orderBy('order')->with(['labels', 'assignee']);
            }])
            ->orderBy('order')
            ->get();

        // Refresh the page
        $this->dispatch('kanban-updated');

        return view('livewire.project.board.board-manager', [
            'lists' => $lists,
        ]);
    }

    /**
     * Returns the listeners
     * 
     * @return array Listeners
     */
    protected function getListeners()
    {
        return [
            'lists-reordered' => 'handleListReorder',
            'tasks-reordered' => 'handleTaskReorder'
        ];
    }

    /**
     * Handles the list reorder
     * 
     * @param array $args Event arguments
     * @return void
     */
    public function handleListReorder(...$args)
    {
        $lists = $args[0] ?? [];
        if (empty($lists)) return;
        
        $this->projectService->reorderTaskLists($lists);
        $this->shouldRenderKanban = true;
    }

    /**
     * Handles the task reorder
     * 
     * @param array $args Event arguments
     * @return void
     */
    public function handleTaskReorder(...$args)
    {
        $data = $args[0] ?? [];
        if (empty($data['tasks']) || !isset($data['targetListId'])) return;
        
        $this->projectService->reorderTasks($data['tasks'], $data['targetListId']);
        $this->shouldRenderKanban = true;
    }

    /**
     * Adds a new checklist item
     * 
     * @return void
     */
    public function addChecklistItem()
    {
        if (!isset($this->taskData['checklist'])) {
            $this->taskData['checklist'] = [];
        }
        
        $this->taskData['checklist'][] = [
            'text' => '',
            'completed' => false
        ];
    }

    /**
     * Removes a checklist item
     * 
     * @param int $index Index of the item to remove
     * @return void
     */
    public function removeChecklistItem($index)
    {
        unset($this->taskData['checklist'][$index]);
        $this->taskData['checklist'] = array_values($this->taskData['checklist']);
    }
} 