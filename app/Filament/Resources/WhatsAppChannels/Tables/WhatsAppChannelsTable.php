<?php

namespace App\Filament\Resources\WhatsAppChannels\Tables;

use App\Models\WhatsAppChannel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class WhatsAppChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('admin.number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel_id')
                    ->label(__('admin.channel_id'))
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('admin.label'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'gray',
                        'assigned' => 'success',
                        'retired' => 'danger',
                        'suspended' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('company.name')->label(__('admin.assigned_company')),
                TextColumn::make('last_inbound_at')
                    ->label(__('admin.last_inbound'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('webhook_ok')
                    ->label(__('admin.webhook'))
                    ->badge()
                    ->state(fn ($record) => $record->webhook_ok ? 'OK' : 'Error')
                    ->color(fn ($record) => $record->webhook_ok ? 'success' : 'danger'),
            ])
            ->filters([])
            ->recordActions([
                Action::make('assign')
                    ->label(__('admin.assign'))
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn (WhatsAppChannel $record) => $record->status === 'available')
                    ->form([
                        Select::make('company_id')
                            ->label(__('admin.company'))
                            ->relationship('company', 'name')
                            ->required(),
                    ])
                    ->action(function (WhatsAppChannel $record, array $data) {
                        $record->update([
                            'assigned_company_id' => $data['company_id'],
                            'status' => 'assigned',
                        ]);
                    }),
                Action::make('release')
                    ->label(__('admin.release'))
                    ->icon('heroicon-o-link-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (WhatsAppChannel $record) => $record->status === 'assigned')
                    ->action(function (WhatsAppChannel $record) {
                        $record->update([
                            'status' => 'available',
                            'assigned_company_id' => null,
                        ]);
                    }),
                Action::make('retire')
                    ->label(__('admin.retired_status'))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn (WhatsAppChannel $record): bool => $record->status !== 'retired')
                    ->action(fn (WhatsAppChannel $record) => $record->update(['status' => 'retired'])),
                Action::make('make_available')
                    ->label(__('admin.make_available'))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (WhatsAppChannel $record): bool => $record->status !== 'available')
                    ->action(fn (WhatsAppChannel $record) => $record->update([
                        'status' => 'available',
                        'assigned_company_id' => null,
                    ])),
                Action::make('register_webhook')
                    ->label(__('admin.re_register_webhook'))
                    ->icon('heroicon-o-globe-alt')
                    ->color('info')
                    ->action(function (WhatsAppChannel $record) {
                        $url = $record->assigned_company_id
                            ? route('webhooks.whatsapp', ['company' => $record->assigned_company_id])
                            : url('/webhooks/whatsapp');

                        $response = Http::withToken(config('services.dialog360.api_key'))
                            ->timeout(10)
                            ->post(rtrim(config('services.dialog360.base_url'), '/').'/configs/webhook', [
                                'url' => $url,
                                'headers' => [
                                    'Authorization' => 'Bearer '.config('app.key'),
                                ],
                            ]);

                        $record->update(['webhook_ok' => $response->successful()]);
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
