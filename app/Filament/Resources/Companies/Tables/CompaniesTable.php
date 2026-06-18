<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'starter' => 'gray',
                        'growth' => 'info',
                        'enterprise' => 'success',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('whatsapp_number')
                    ->label('Assigned Number')
                    ->searchable(),
                TextColumn::make('conversations_count')
                    ->label('Usage This Cycle')
                    ->state(function (Company $record) {
                        $limit = $record->getPlanDetails()['conversations_limit'];
                        $limitText = $limit === -1 ? '∞' : $limit;

                        return "{$record->conversations_count} / {$limitText}";
                    }),
                TextColumn::make('cost')
                    ->label('Est. Cost')
                    ->state(function (Company $record) {
                        $billingStart = $record->billing_cycle_start;
                        $billingEnd = $record->billing_cycle_end;

                        $query = Message::where('company_id', $record->id);
                        if ($billingStart && $billingEnd) {
                            $query->whereBetween('created_at', [$billingStart, $billingEnd]);
                        }
                        $tokens = $query->selectRaw('SUM(input_tokens) as total_input, SUM(output_tokens) as total_output')
                            ->first();

                        $inputCost = (($tokens?->total_input ?? 0) / 1000) * 0.00025;
                        $outputCost = (($tokens?->total_output ?? 0) / 1000) * 0.00125;
                        $whatsappCost = ($record->conversations_count ?? 0) * 0.03;

                        return '$'.number_format($inputCost + $outputCost + $whatsappCost, 2);
                    }),
                TextColumn::make('revenue')
                    ->label('Est. Revenue')
                    ->state(function (Company $record) {
                        $revenue = match ($record->plan) {
                            'starter' => 49.00,
                            'growth' => 149.00,
                            'enterprise' => 499.00,
                            default => 0.00,
                        };

                        return '$'.number_format($revenue, 2);
                    }),
                TextColumn::make('margin')
                    ->label('Margin')
                    ->state(function (Company $record) {
                        $billingStart = $record->billing_cycle_start;
                        $billingEnd = $record->billing_cycle_end;

                        $query = Message::where('company_id', $record->id);
                        if ($billingStart && $billingEnd) {
                            $query->whereBetween('created_at', [$billingStart, $billingEnd]);
                        }
                        $tokens = $query->selectRaw('SUM(input_tokens) as total_input, SUM(output_tokens) as total_output')
                            ->first();

                        $inputCost = (($tokens?->total_input ?? 0) / 1000) * 0.00025;
                        $outputCost = (($tokens?->total_output ?? 0) / 1000) * 0.00125;
                        $whatsappCost = ($record->conversations_count ?? 0) * 0.03;
                        $cost = $inputCost + $outputCost + $whatsappCost;

                        $revenue = match ($record->plan) {
                            'starter' => 49.00,
                            'growth' => 149.00,
                            'enterprise' => 499.00,
                            default => 0.00,
                        };

                        $margin = $revenue - $cost;

                        return '$'.number_format($margin, 2);
                    })
                    ->color(fn (string $state): string => str_contains($state, '-') ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Company $record): bool => (bool) $record->is_active)
                    ->action(fn (Company $record) => $record->update(['is_active' => false])),
                Action::make('reactivate')
                    ->label('Reactivate')
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Company $record): bool => ! (bool) $record->is_active)
                    ->action(fn (Company $record) => $record->update(['is_active' => true])),
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-user')
                    ->color('warning')
                    ->action(function (Company $record) {
                        $owner = $record->users()->where('role', UserRole::Owner)->first();
                        if ($owner) {
                            Auth::login($owner);

                            return redirect()->to(route('dashboard'));
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
