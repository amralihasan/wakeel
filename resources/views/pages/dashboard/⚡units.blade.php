<?php

use App\Models\Unit;
use App\Models\UnitMedia;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('dashboard.units')] class extends Component {
    use WithFileUploads;

    public string $searchQuery = '';

    public string $filterStatus = '';

    public ?int $unitId = null;

    public string $title = '';

    public string $description = '';

    public string $type = 'apartment';

    public int $rooms = 1;

    public int $area = 50;

    public int $price = 500000;

    public string $location = '';

    public ?int $down_payment = null;

    public ?int $installment_years = null;

    public ?string $delivery_date = null;

    public string $status = 'available';

    public array $newImages = [];

    public $newPdf = null;

    public $newFloorplan = null;

    public ?string $videoUrl = null;

    public bool $showForm = false;

    #[Computed]
    public function units()
    {
        $query = Unit::where('company_id', Auth::user()->company_id)
            ->withCount([
                'media as images_count' => fn ($q) => $q->where('type', 'image'),
                'media as pdfs_count' => fn ($q) => $q->where('type', 'pdf'),
                'media as floorplans_count' => fn ($q) => $q->where('type', 'floorplan'),
                'media as videos_count' => fn ($q) => $q->where('type', 'video'),
            ])
            ->orderBy('created_at', 'desc');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->searchQuery}%")
                    ->orWhere('location', 'like', "%{$this->searchQuery}%");
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->paginate(12);
    }

    #[Computed]
    public function editingUnit()
    {
        if (! $this->unitId) {
            return null;
        }

        return Unit::with('media')->find($this->unitId);
    }

    public function save(): void
    {
        Gate::authorize('manageUnits', Auth::user()->company);

        if (! $this->unitId && Auth::user()->company->hasReachedUnitsLimit()) {
            $this->addError('title', __('units.limit_reached'));

            return;
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:apartment,duplex,penthouse,villa,twinhouse,townhouse,standalone,chalet,studio,compound,building,office,retail,clinic,land',
            'rooms' => 'required|integer|min:1',
            'area' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'location' => 'required|string|max:255',
            'down_payment' => 'nullable|integer|min:0',
            'installment_years' => 'nullable|integer|min:1',
            'delivery_date' => 'nullable|date',
            'status' => 'required|in:available,reserved,sold',
            'newImages' => 'nullable|array',
            'newImages.*' => 'image|max:5120',
            'newPdf' => 'nullable|file|mimes:pdf|max:10240',
            'newFloorplan' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'videoUrl' => 'nullable|url',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'rooms' => $this->rooms,
            'area' => $this->area,
            'price' => $this->price,
            'location' => $this->location,
            'down_payment' => $this->down_payment,
            'installment_years' => $this->installment_years,
            'delivery_date' => $this->delivery_date,
            'status' => $this->status,
        ];

        if ($this->unitId) {
            $unit = Unit::findOrFail($this->unitId);
            $unit->update($data);
        } else {
            $unit = Unit::create($data);
        }

        foreach ($this->newImages as $image) {
            $path = $image->store("units/{$unit->id}/images", 'public');
            $maxSort = UnitMedia::where('unit_id', $unit->id)->max('sort_order') ?? 0;
            UnitMedia::create([
                'unit_id' => $unit->id,
                'type' => 'image',
                'path' => $path,
                'sort_order' => $maxSort + 1,
            ]);
        }

        if ($this->newPdf) {
            $path = $this->newPdf->store("units/{$unit->id}/docs", 'public');
            UnitMedia::create(['unit_id' => $unit->id, 'type' => 'pdf', 'path' => $path]);
        }

        if ($this->newFloorplan) {
            $path = $this->newFloorplan->store("units/{$unit->id}/floorplans", 'public');
            UnitMedia::create(['unit_id' => $unit->id, 'type' => 'floorplan', 'path' => $path]);
        }

        if ($this->videoUrl) {
            UnitMedia::create(['unit_id' => $unit->id, 'type' => 'video', 'path' => $this->videoUrl]);
        }

        $this->resetForm();
        Flux::toast(variant: 'success', text: $this->unitId ? __('units.updated_success') : __('units.created_success'));
    }

    public function edit(int $id): void
    {
        Gate::authorize('manageUnits', Auth::user()->company);

        $unit = Unit::findOrFail($id);
        $this->unitId = $unit->id;
        $this->title = $unit->title;
        $this->description = $unit->description;
        $this->type = $unit->type;
        $this->rooms = $unit->rooms;
        $this->area = $unit->area;
        $this->price = $unit->price;
        $this->location = $unit->location;
        $this->down_payment = $unit->down_payment;
        $this->installment_years = $unit->installment_years;
        $this->delivery_date = $unit->delivery_date;
        $this->status = $unit->status->value;

        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Gate::authorize('manageUnits', Auth::user()->company);

        $unit = Unit::findOrFail($id);

        foreach ($unit->media as $media) {
            if ($media->type !== 'video') {
                Storage::disk('public')->delete($media->path);
            }
            $media->delete();
        }

        $unit->delete();

        Flux::toast(variant: 'success', text: __('units.deleted_success'));
    }

    public function deleteMedia(int $mediaId): void
    {
        Gate::authorize('manageUnits', Auth::user()->company);

        $media = UnitMedia::findOrFail($mediaId);

        Unit::where('company_id', Auth::user()->company_id)
            ->where('id', $media->unit_id)
            ->firstOrFail();

        if ($media->type !== 'video') {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();

        Flux::toast(variant: 'success', text: __('units.file_deleted_success'));
    }

    public function reorderMedia(array $orderedIds): void
    {
        Gate::authorize('manageUnits', Auth::user()->company);

        $media = UnitMedia::whereIn('id', $orderedIds)->get();
        if ($media->isEmpty()) {
            return;
        }

        $unitId = $media->first()->unit_id;

        Unit::where('company_id', Auth::user()->company_id)
            ->where('id', $unitId)
            ->firstOrFail();

        foreach ($orderedIds as $index => $id) {
            UnitMedia::where('id', $id)->where('unit_id', $unitId)->update(['sort_order' => $index + 1]);
        }
    }

    public function openCreateForm(): void
    {
        Gate::authorize('manageUnits', Auth::user()->company);

        if (Auth::user()->company->hasReachedUnitsLimit()) {
            Flux::toast(variant: 'danger', text: __('units.limit_reached'));

            return;
        }

        $this->resetForm();
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->unitId = null;
        $this->title = '';
        $this->description = '';
        $this->type = 'apartment';
        $this->rooms = 1;
        $this->area = 50;
        $this->price = 500000;
        $this->location = '';
        $this->down_payment = null;
        $this->installment_years = null;
        $this->delivery_date = null;
        $this->status = 'available';
        $this->newImages = [];
        $this->newPdf = null;
        $this->newFloorplan = null;
        $this->videoUrl = null;
        $this->showForm = false;
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    @if (! $showForm)
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-bold">{{ __('dashboard.units') }}</h1>

            <div class="flex flex-wrap items-center gap-3">
                <flux:input wire:model.live="searchQuery" :placeholder="__('units.search_placeholder')" class="min-w-[200px]" />

                <flux:select wire:model.live="filterStatus" :placeholder="__('units.status')">
                    <option value="">{{ __('units.all_statuses') }}</option>
                    <option value="available">{{ __('units.available') }}</option>
                    <option value="reserved">{{ __('units.reserved') }}</option>
                    <option value="sold">{{ __('units.sold') }}</option>
                </flux:select>

                @can('manageUnits', auth()->user()->company)
                    <flux:button variant="primary" wire:click="openCreateForm">{{ __('units.add_unit_btn') }}</flux:button>
                @endcan
            </div>
        </div>

        {{-- Units Grid --}}
        <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($this->units as $unit)
                <div class="flex flex-col rounded-xl border border-neutral-200 dark:border-neutral-700">
                    @php $image = $unit->media()->where('type', 'image')->orderBy('sort_order')->first(); @endphp
                    <div class="aspect-video w-full overflow-hidden rounded-t-xl bg-neutral-100 dark:bg-neutral-800">
                        @if ($image)
                            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $unit->title }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full items-center justify-center text-neutral-400">
                                <flux:icon name="photo" class="h-10 w-10" />
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col gap-2 p-3">
                        <h3 class="font-semibold">{{ $unit->title }}</h3>

                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-neutral-500">
                            <span>{{ __('units.'.$unit->type) }}</span>
                            <span>{{ $unit->rooms }} {{ __('units.rooms_suffix') }}</span>
                            <span>{{ $unit->area }} {{ __('units.area_suffix') }}</span>
                        </div>

                        <p class="text-xs text-neutral-500">{{ $unit->location }}</p>

                        <p class="text-sm font-bold">{{ number_format($unit->price) }} {{ __('units.currency_suffix') }}</p>

                        <div class="flex items-center gap-2">
                            @php
                                $badgeMap = ['available' => 'success', 'reserved' => 'warning', 'sold' => 'danger'];
                                $labelMap = [
                                    'available' => __('units.available'),
                                    'reserved' => __('units.reserved'),
                                    'sold' => __('units.sold'),
                                ];
                            @endphp
                            <flux:badge variant="{{ $badgeMap[$unit->status->value] }}" size="sm">{{ $labelMap[$unit->status->value] }}</flux:badge>
                        </div>

                        <div class="flex items-center gap-3 text-[11px] text-neutral-400">
                            @if ($unit->images_count > 0)
                                <span>📷 {{ $unit->images_count }}</span>
                            @endif
                            @if ($unit->pdfs_count > 0)
                                <span>📄 {{ $unit->pdfs_count }}</span>
                            @endif
                            @if ($unit->floorplans_count > 0)
                                <span>📐 {{ $unit->floorplans_count }}</span>
                            @endif
                            @if ($unit->videos_count > 0)
                                <span>🎬 {{ $unit->videos_count }}</span>
                            @endif
                        </div>

                        @can('manageUnits', auth()->user()->company)
                            <div class="mt-auto flex items-center gap-2 pt-2">
                                <flux:button size="xs" wire:click="edit({{ $unit->id }})">{{ __('units.edit') }}</flux:button>
                                <flux:button size="xs" variant="danger" wire:click="delete({{ $unit->id }})" wire:confirm="{{ __('units.delete_confirm') }}">{{ __('units.delete') }}</flux:button>
                            </div>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-span-full flex items-center justify-center py-20">
                    <p class="text-neutral-500">{{ __('units.no_units') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-4" dir="ltr">
            {{ $this->units->links() }}
        </div>
    @else
        {{-- Create/Edit Form (Inline Page, not overlay) --}}
        <div class="w-full rounded-xl bg-white p-6 border border-neutral-200 dark:border-neutral-700 dark:bg-zinc-900 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-bold">{{ $unitId ? __('units.edit_unit') : __('units.add_unit') }}</h2>
                <flux:button size="sm" variant="ghost" wire:click="resetForm">✕</flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                {{-- Basic Info --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('units.basic_info') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="title" :label="__('units.title_label')" required class="col-span-2" />
                        <flux:select wire:model="type" :label="__('units.type')" required>
                            <option value="apartment">{{ __('units.apartment') }}</option>
                            <option value="duplex">{{ __('units.duplex') }}</option>
                            <option value="penthouse">{{ __('units.penthouse') }}</option>
                            <option value="villa">{{ __('units.villa') }}</option>
                            <option value="twinhouse">{{ __('units.twinhouse') }}</option>
                            <option value="townhouse">{{ __('units.townhouse') }}</option>
                            <option value="standalone">{{ __('units.standalone') }}</option>
                            <option value="chalet">{{ __('units.chalet') }}</option>
                            <option value="studio">{{ __('units.studio') }}</option>
                            <option value="compound">{{ __('units.compound') }}</option>
                            <option value="building">{{ __('units.building') }}</option>
                            <option value="office">{{ __('units.office') }}</option>
                            <option value="retail">{{ __('units.retail') }}</option>
                            <option value="clinic">{{ __('units.clinic') }}</option>
                            <option value="land">{{ __('units.land') }}</option>
                        </flux:select>
                        <flux:input wire:model="rooms" :label="__('units.rooms')" type="number" required />
                        <flux:input wire:model="area" :label="__('units.area_label')" type="number" required />
                        <flux:input wire:model="location" :label="__('units.location_label')" required class="col-span-2" />
                    </div>
                    <flux:textarea wire:model="description" :label="__('units.description_label')" required rows="3" />
                </div>

                {{-- Pricing --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('units.price_payment') }}</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <flux:input wire:model="price" :label="__('units.price')" type="number" required />
                        <flux:input wire:model="down_payment" :label="__('units.down_payment_label')" type="number" />
                        <flux:input wire:model="installment_years" :label="__('units.installment_years')" type="number" />
                    </div>
                </div>

                {{-- Status & Dates --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('units.status_dates') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="status" :label="__('units.status')" required>
                            <option value="available">{{ __('units.available') }}</option>
                            <option value="reserved">{{ __('units.reserved') }}</option>
                            <option value="sold">{{ __('units.sold') }}</option>
                        </flux:select>
                        <flux:input wire:model="delivery_date" :label="__('units.delivery_date')" type="date" />
                    </div>
                </div>

                {{-- Media Upload --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('units.media_title') }}</h3>

                    <flux:input wire:model="newImages" :label="__('units.images_upload_label')" type="file" multiple accept="image/*" />

                    @if ($newImages)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($newImages as $index => $image)
                                <div class="relative">
                                    <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded-lg object-cover" />
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="newPdf" :label="__('units.pdf_upload_label')" type="file" accept=".pdf" />
                        <flux:input wire:model="newFloorplan" :label="__('units.floorplan_upload_label')" type="file" accept=".pdf,.jpg,.png" />
                    </div>

                    <flux:input wire:model="videoUrl" :label="__('units.video_url_label')" type="url" placeholder="https://" />
                </div>

                {{-- Existing Media (edit mode) --}}
                @if ($this->editingUnit && $this->editingUnit->media->where('type', 'image')->count() > 0)
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('units.image_reorder_label') }}</h3>
                        <div x-data="{
                            dragging: null,
                            dragStart(index) {
                                this.dragging = index;
                            },
                            drop($event, index) {
                                if (this.dragging === null || this.dragging === index) return;
                                const container = $event.currentTarget.closest('[data-reorder-list]');
                                const items = [...container.querySelectorAll('[data-media-id]')];
                                const ids = items.map(el => parseInt(el.dataset.mediaId));
                                const [moved] = ids.splice(this.dragging, 1);
                                ids.splice(index, 0, moved);
                                $wire.reorderMedia(ids);
                                this.dragging = null;
                            }
                        }">
                            <div data-reorder-list class="flex flex-wrap gap-2">
                                @foreach ($this->editingUnit->media->where('type', 'image')->sortBy('sort_order') as $media)
                                    <div data-media-id="{{ $media->id }}"
                                         draggable="true"
                                         @dragstart="dragStart({{ $loop->index }})"
                                         @dragover.prevent
                                         @drop="drop($event, {{ $loop->index }})"
                                         class="relative cursor-grab active:cursor-grabbing"
                                         :class="{ 'opacity-50': dragging === {{ $loop->index }} }">
                                        <img src="{{ Storage::disk('public')->url($media->path) }}" class="h-20 w-20 rounded-lg object-cover" />
                                        <button type="button" wire:click="deleteMedia({{ $media->id }})" class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] text-white hover:bg-red-600">×</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                    <flux:button variant="ghost" wire:click="resetForm">{{ __('units.cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('units.save') }}</flux:button>
                </div>
            </form>
        </div>
    @endif
</div>
