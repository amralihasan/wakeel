<?php

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('team.title')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $showInviteForm = false;

    #[Computed]
    public function reps()
    {
        return Auth::user()->company->users()
            ->where('role', UserRole::SalesRep)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openInviteForm(): void
    {
        if (Auth::user()->company->hasReachedRepsLimit()) {
            Flux::toast(variant: 'danger', text: __('team.seats_limit'));
            return;
        }

        $this->resetForm();
        $this->showInviteForm = true;
    }

    public function save(): void
    {
        $company = Auth::user()->company;

        if ($company->hasReachedRepsLimit()) {
            $this->addError('email', __('team.seats_limit'));
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'role' => UserRole::SalesRep,
        ]);

        $this->resetForm();
        Flux::toast(variant: 'success', text: __('team.success_invited'));
    }

    public function delete(int $id): void
    {
        $rep = User::where('company_id', Auth::user()->company_id)
            ->where('id', $id)
            ->where('role', UserRole::SalesRep)
            ->firstOrFail();

        $rep->delete();

        Flux::toast(variant: 'success', text: __('team.success_removed'));
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->showInviteForm = false;
    }
}; ?>

<div class="space-y-6" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('team.title') }}</h1>
        <flux:button variant="primary" wire:click="openInviteForm">+ {{ __('team.add_member') }}</flux:button>
    </div>

    {{-- Reps list --}}
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
            @forelse ($this->reps as $rep)
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="font-medium">{{ $rep->name }}</p>
                        <p class="text-sm text-neutral-500">{{ $rep->email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button size="xs" variant="danger" wire:click="delete({{ $rep->id }})" wire:confirm="{{ __('team.confirm_delete', ['name' => $rep->name]) }}">{{ __('team.remove_member') }}</flux:button>
                    </div>
                </div>
            @empty
                <p class="p-4 text-center text-sm text-neutral-500">{{ __('team.no_reps') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Invite form --}}
    @if ($showInviteForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 py-10">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ __('team.add_member') }}</h2>
                    <flux:button size="sm" wire:click="resetForm">✕</flux:button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <flux:input wire:model="name" label="{{ __('team.name') }}" required />
                    <flux:input wire:model="email" label="{{ __('team.email') }}" type="email" required />
                    <flux:input wire:model="password" label="{{ __('team.password') }}" type="password" required />
                    <flux:input wire:model="password_confirmation" label="{{ __('team.password_confirmation') }}" type="password" required />

                    @error('email')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                        <flux:button variant="ghost" wire:click="resetForm">{{ __('units.cancel') }}</flux:button>
                        <flux:button variant="primary" type="submit">{{ __('team.invite') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
