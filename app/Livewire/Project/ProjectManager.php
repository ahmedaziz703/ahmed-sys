<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Services\Project\Contracts\ProjectServiceInterface;
use App\DTOs\Project\ProjectData;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Project Manager Component
 * 
 * This component provides functionality to manage projects.
 * Features:
 * - Project list view
 * - New project creation
 * - Project editing
 * - Project deletion
 * - Project status tracking
 * - Project filtering
 * - Project board management   
 * 
 * @package App\Livewire\Project
 */
class ProjectManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var ProjectServiceInterface Project service */
    private ProjectServiceInterface $projectService;

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
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Project::query())
            ->emptyStateHeading('لم يتم العثور على مشروع')
            ->emptyStateDescription('أنشاء مشروع جديد')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المشروع')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('الوصف')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحاله')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشيط',
                        'completed' => 'مكتمل',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('انشاء مشروع')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحاله')
                    ->options([
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                    ])
                    ->native(false)
            ])
            ->actions([
                Tables\Actions\Action::make('boards')
                    ->label('إدارة المشاريع')
                    ->url(fn (Project $record): string => route('admin.projects.boards', $record))
                    ->icon('heroicon-m-squares-2x2')
                    ->visible(fn () => auth()->user()->can('projects.details')),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل المشروع')
                    ->visible(fn () => auth()->user()->can('projects.edit'))
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المشروع')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف'),
                        Forms\Components\Select::make('status')
                            ->label('الحاله')
                            ->options([
                                'active' => 'نشط',
                                'completed' => 'مكتمل',
                            ])
                            ->native(false)
                            ->required(),
                    ])
                    ->using(function (Project $record, array $data): Project {
                        return $this->projectService->update($record, ProjectData::fromArray($data));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => auth()->user()->can('projects.delete'))
                    ->using(function (Project $record): void {
                        $this->projectService->delete($record);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('أنشاء مشروع')
                    ->modalHeading('مشروع جديد')
                    ->visible(fn () => auth()->user()->can('projects.create'))
                    ->createAnother(false)
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المشروع')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف'),
                        Forms\Components\Select::make('status')
                            ->label('الحاله')
                            ->options([
                                'active' => 'نشط',
                                'completed' => 'مكتمل',
                            ])
                            ->default('active')
                            ->native(false)
                            ->required(),
                    ])
                    ->using(function (array $data): Project {
                        return $this->projectService->create(ProjectData::fromArray([
                            ...$data,
                            'created_by' => auth()->id(),
                        ]));
                    }),
            ]);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.project.project-manager');
    }
} 