<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

/**
 * Category Management Component
 * 
 * This component manages the categories of income and expense.
 * Features:
 * - Category list view
 * - Create new category
 * - Edit category
 * - Delete category
 * - Category filtering (income/expense)
 * - Category status management
 * 
 * @package App\Livewire\Categories
 */
final class CategoryManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Category::query())
            ->defaultGroup(
                Tables\Grouping\Group::make('type')
                    ->label('نوع')
                    ->getTitleFromRecordUsing(fn (Category $record): string => match ($record->type) {
                        'income' => 'ايرادات',
                        'expense' => 'نفقات',
                        default => ucfirst($record->type),
                    })
            )
            ->emptyStateHeading('فئة لم يتم العثور عليه')
            ->emptyStateDescription('أنشاء فئة جديد.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الفئه')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'ايرادات',
                        'expense' => 'نفقات',
                    })
                    , // groupable() kaldırıldı
                Tables\Columns\ColorColumn::make('color')
                    ->label('لون'),
                Tables\Columns\IconColumn::make('status')
                    ->label('الوصف')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'income' => 'ايرادات',
                        'expense' => 'نفقات',
                    ])
                    ->placeholder('جميع الأنواع')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('تعديل الفئه')
                    ->modalSubmitActionLabel('تحديث')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('تم التحديث بنجاح')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الفئة')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('نوع')
                            ->options([
                                'income' => 'ايرادات',
                                'expense' => 'نفقات',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\ColorPicker::make('color')
                            ->label('لون'),
                        Forms\Components\Toggle::make('status')
                            ->label('الوصف')
                            ->default(true),
                    ])
                    ->visible(auth()->user()->can('categories.edit')),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('حذف الفئه')
                    ->modalDescription('هل أنت متأكد أنك تريد حذف هذه الفئه')
                    ->modalSubmitActionLabel('حذف')
                    ->modalCancelActionLabel('الغاء')
                    ->successNotificationTitle('تم الحذف بنجاح')
                    ->visible(auth()->user()->can('categories.delete')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('أضافه فئة')
                    ->modalHeading('فئة جديده')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalCancelActionLabel('الغاء')
                    ->createAnother(false)
                    ->successNotificationTitle('تمت الاضافه بنجاح')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الفئة')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('نوع')
                            ->options([
                                'income' => 'ايردادت',
                                'expense' => 'نفقات',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\ColorPicker::make('color')
                            ->label('لون'),
                        Forms\Components\Toggle::make('status')
                            ->label('الوصف')
                            ->default(true),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->visible(auth()->user()->can('categories.create')),
            ]);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.categories.category-manager');
    }
} 