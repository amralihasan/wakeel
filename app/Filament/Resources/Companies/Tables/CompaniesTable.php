<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\AdminAuditLog;
use App\Models\Company;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.company_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan')
                    ->label(__('admin.plan'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'starter' => 'gray',
                        'growth' => 'info',
                        'enterprise' => 'success',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label(__('admin.active')),
                TextColumn::make('whatsapp_number')
                    ->label(__('admin.assigned_number'))
                    ->searchable(),
                TextColumn::make('conversations_count')
                    ->label(__('admin.usage_this_cycle'))
                    ->state(function (Company $record) {
                        $limit = $record->getPlanDetails()['conversations_limit'];
                        $limitText = $limit === -1 ? '∞' : $limit;

                        return "{$record->conversations_count} / {$limitText}";
                    }),
                TextColumn::make('cost')
                    ->label(__('admin.est_cost'))
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
                    ->label(__('admin.est_revenue'))
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
                    ->label(__('admin.margin'))
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
                SelectFilter::make('plan')
                    ->label(__('admin.plan'))
                    ->options([
                        'starter' => __('admin.starter'),
                        'growth' => __('admin.growth'),
                        'enterprise' => __('admin.enterprise'),
                    ]),
                SelectFilter::make('is_active')
                    ->label(__('admin.status'))
                    ->options([
                        '1' => __('admin.active'),
                        '0' => __('admin.suspended'),
                    ]),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('admin.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Company $record) => "/admin/companies/{$record->id}"),
                Action::make('suspend')
                    ->label(__('admin.suspend'))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Company $record): bool => (bool) $record->is_active)
                    ->action(function (Company $record) {
                        $record->update(['is_active' => false]);
                        AdminAuditLog::record(
                            auth()->user(),
                            'company_suspended',
                            'company',
                            $record->id,
                            "Suspended company {$record->name}",
                            ['is_active' => true],
                            ['is_active' => false],
                        );
                    }),
                Action::make('reactivate')
                    ->label(__('admin.reactivate'))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Company $record): bool => ! (bool) $record->is_active)
                    ->action(function (Company $record) {
                        $record->update(['is_active' => true]);
                        AdminAuditLog::record(
                            auth()->user(),
                            'company_reactivated',
                            'company',
                            $record->id,
                            "Reactivated company {$record->name}",
                            ['is_active' => false],
                            ['is_active' => true],
                        );
                    }),
                Action::make('impersonate')
                    ->label(__('admin.impersonate'))
                    ->icon('heroicon-o-user')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Company $record) {
                        $owner = $record->users()->where('role', 'owner')->first();
                        if ($owner) {
                            AdminAuditLog::record(
                                auth()->user(),
                                'impersonation_started',
                                'company',
                                $record->id,
                                "Impersonated company {$record->name} owner",
                            );
                            Auth::login($owner);
                            session()->put('impersonating', true);

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
