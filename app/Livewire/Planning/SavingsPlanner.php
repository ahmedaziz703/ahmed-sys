<?php

declare(strict_types=1);

namespace App\Livewire\Planning;

use App\Models\SavingsPlan;
use Livewire\Component;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Planning\Contracts\PlanningServiceInterface;

/**
 * Savings Planner Component
 * 
 * This component provides functionality to manage savings plans.
 * Features:
 * - Savings plan list view
 * - New savings plan creation
 * - Savings plan editing
 * - Savings plan deletion
 * - Savings status tracking
 * - Savings filtering
 * - Bulk action support
 * 
 * @package App\Livewire\Planning
 */
final class SavingsPlanner extends Component implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    /** @var PlanningServiceInterface Planning service */
    private PlanningServiceInterface $planningService;

    /**
     * When the component is booted, the planning service is injected
     * 
     * @param PlanningServiceInterface $planningService Planning service
     * @return void
     */
    public function boot(PlanningServiceInterface $planningService): void
    {
        $this->planningService = $planningService;
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
            ->query(SavingsPlan::query())
            ->emptyStateHeading('لا توجد خطط ادخار')
            ->emptyStateDescription('أنشئ خطة ادخار جديدة.')
            ->columns([
                TextColumn::make('goal_name')
                    ->label('اسم الهدف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_amount')
                    ->label('المبلغ المستهدف')
                    ->money('YER')
                    ->sortable(),
                TextColumn::make('saved_amount')
                    ->label('المبلغ المتراكم')
                    ->money('YER')
                    ->sortable(),
                TextColumn::make('target_date')
                    ->label('التاريخ المستهدف')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray', // ← هذا السطر ضروري لتفادي الخطأ
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغى',
                        default => 'غير معروف', // ← معالجة القيم مثل 1 أو 0
                    })
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->native(false)
                    ->options([
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغى',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل خطة الادخار')
                    ->form([
                        TextInput::make('goal_name')
                            ->label('اسم الهدف')
                            ->required(),
                        TextInput::make('target_amount')
                            ->label('المبلغ المستهدف')
                            ->numeric()
                            ->required(),
                        TextInput::make('saved_amount')
                            ->label('المبلغ المتراكم')
                            ->numeric()
                            ->required(),
                        DatePicker::make('target_date')
                            ->label('التاريخ المستهدف')
                            ->native(false)
                            ->required(),
                        Select::make('status')
                            ->label('الحالة')
                            ->native(false)
                            ->options([
                                'active' => 'نشط',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغى',
                            ])
                            ->required(),
                    ])
                    ->action(function (SavingsPlan $record, array $data): SavingsPlan {
                        return $this->planningService->updateSavingsPlan($record, $data);
                    })
                    ->visible(auth()->user()->can('savings.edit')),
                DeleteAction::make()
                    ->label('حذف')
                    ->modalHeading('حذف خطة الادخار')
                    ->action(function (SavingsPlan $record): void {
                        $this->planningService->deleteSavingsPlan($record);
                    })
                    ->visible(auth()->user()->can('savings.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(auth()->user()->can('savings.delete')),
                ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('إنشاء خطة ادخار')
                    ->modalHeading('خطة ادخار جديدة')
                    ->form([
                        TextInput::make('goal_name')
                            ->label('اسم الهدف')
                            ->required(),
                        TextInput::make('target_amount')
                            ->label('المبلغ المستهدف')
                            ->numeric()
                            ->required(),
                        TextInput::make('saved_amount')
                            ->label('المبلغ المتراكم')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        DatePicker::make('target_date')
                            ->label('التاريخ المستهدف')
                            ->native(false)
                            ->required(),
                        Select::make('status')
                            ->label('الحالة')
                            ->native(false)
                            ->options([
                                'active' => 'نشط',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغى',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->action(function (array $data): SavingsPlan {
                        return $this->planningService->createSavingsPlan($data);
                    })
                    ->modalSubmitActionLabel('حفظ')
                    ->visible(auth()->user()->can('savings.create')),
            ]);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.planning.savings-planner');
    }
}