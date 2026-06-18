<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopTenantsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('admin.top_tenants');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Company::query()
                    ->where('is_active', true)
                    ->orderByDesc('conversations_count')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.company_name'))
                    ->searchable(),
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
                TextColumn::make('conversations_count')
                    ->label(__('admin.usage_this_cycle'))
                    ->sortable(),
                TextColumn::make('revenue')
                    ->label(__('admin.est_revenue'))
                    ->state(function (Company $record) {
                        return '$'.number_format(match ($record->plan) {
                            'starter' => 49.00,
                            'growth' => 149.00,
                            'enterprise' => 499.00,
                            default => 0.00,
                        }, 0);
                    }),
                TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->date()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
