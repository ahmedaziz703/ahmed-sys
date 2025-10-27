<?php

namespace App\Livewire\Proposal;

use App\Models\ProposalTemplate;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Proposal Template Manager Component
 * 
 * This component provides functionality to manage proposal templates.
 * Features:
 * - Proposal template list view
 * - New proposal template creation
 * - Proposal template editing
 * - Proposal template deletion
 * - Proposal template status tracking
 * - PDF output
 * - Email sending
 * 
 * @package App\Livewire\Proposal
 */
class ProposalTemplateManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Creates the table query
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getTableQuery()
    {
        return ProposalTemplate::query()
            ->with(['customer', 'items'])
            ->latest();
    }

    /**
     * Returns the table empty state heading
     * 
     * @return string|null
     */
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Teklif لم يتم العثور عليه';
    }

    /**
     * Returns the table empty state description
     * 
     * @return string|null
     */
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'أنشاء teklif أنشاءun.';
    }

    /**
     * Returns the table empty state icon
     * 
     * @return string|null
     */
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    /**
     * Creates the table columns
     * 
     * @return array
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('number')
                ->label('Teklif No')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('customer.name')
                ->label('العميل')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('items')
                ->label('المبلغ الإجمالي')
                ->money('YER')
                ->getStateUsing(fn (ProposalTemplate $record): float => $record->getTotalAmount())
                ->alignEnd(),
            Tables\Columns\TextColumn::make('valid_until')
                ->label('الصلاحية')
                ->date()
                ->sortable()
                ->description(fn (ProposalTemplate $record): string => $record->isExpired() ? 'منتهي الصلاحية' : 'نشط'),
            Tables\Columns\TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'info',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    'expired' => 'warning',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'مسودة',
                    'sent' => 'تم الإرسال',
                    'accepted' => 'تم القبول',
                    'rejected' => 'تم الرفض',
                    'expired' => 'منتهي الصلاحية',
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('creator.name')
                ->label('المنشئ')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable(),
        ];
    }

    /**
     * Creates the table actions
     * 
     * @return array
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->url(fn (ProposalTemplate $record): string => route('admin.proposals.edit', ['template' => $record->id]))
                ->icon('heroicon-m-pencil-square'),
            Tables\Actions\Action::make('pdf')
                ->label('PDF')
                ->url(fn (ProposalTemplate $record): string => route('admin.proposals.pdf', ['proposal' => $record->id]))
                ->icon('heroicon-m-document-arrow-down')
                ->openUrlInNewTab()
                ->color('success'),
            Tables\Actions\Action::make('send')
                ->label('Gönder')
                ->icon('heroicon-m-paper-airplane')
                ->action(function (ProposalTemplate $record) {
                    $record->update(['status' => 'sent']);
                })
                ->requiresConfirmation()
                ->visible(fn (ProposalTemplate $record): bool => $record->status === 'draft'),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    /**
     * Creates the table header actions
     * 
     * @return array
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('Teklif أنشاء')
                ->url(route('admin.proposals.create'))
                ->icon('heroicon-m-plus')
                ->extraAttributes(['wire:navigate' => true]),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.proposal.manager');
    }
}
