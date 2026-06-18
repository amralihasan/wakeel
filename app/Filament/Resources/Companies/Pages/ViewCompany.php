<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\AdminAuditLog;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Details')
                    ->schema([
                        Text::make('name'),
                        Text::make('email'),
                        Text::make('plan')
                            ->badge(),
                        Text::make('is_active')
                            ->label('Status')
                            ->state(fn ($record) => $record->is_active ? 'Active' : 'Suspended')
                            ->badge()
                            ->color(fn ($record) => $record->is_active ? 'success' : 'danger'),
                        Text::make('whatsapp_number')->label('WhatsApp Number'),
                        Text::make('conversations_count')->label('Conversations This Cycle'),
                    ])->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('impersonate')
                ->label('Impersonate Owner')
                ->icon('heroicon-o-user')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $owner = $this->record->users()->where('role', 'owner')->first();
                    if ($owner) {
                        AdminAuditLog::record(
                            auth()->user(),
                            'impersonation_started',
                            'company',
                            $this->record->id,
                            "Impersonated company {$this->record->name} owner ({$owner->email})",
                        );
                        auth()->login($owner);
                        session()->put('impersonating', true);

                        return redirect()->to(route('dashboard'));
                    }
                }),
            Action::make('stop_impersonating')
                ->label('Exit Impersonation')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('danger')
                ->visible(fn () => session('impersonating'))
                ->action(function () {
                    session()->forget(['impersonating', 'impersonated_by']);

                    return redirect()->to('/admin');
                }),
        ];
    }
}
