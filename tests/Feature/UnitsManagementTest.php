<?php

use App\Agent\Tools\SearchPropertiesTool;
use App\Enums\UnitStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Unit;
use App\Models\UnitMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
});

it('allows owners full CRUD on units', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    // Create
    Livewire::test('pages::dashboard.units')
        ->set('title', 'شقة فاخرة')
        ->set('description', 'وصف الشقة الفاخرة')
        ->set('type', 'apartment')
        ->set('rooms', 3)
        ->set('area', 150)
        ->set('price', 1000000)
        ->set('location', 'القاهرة الجديدة')
        ->set('status', 'available')
        ->call('save')
        ->assertSet('showForm', false);

    $unit = Unit::first();
    expect($unit)->not->toBeNull()
        ->and($unit->title)->toBe('شقة فاخرة')
        ->and($unit->company_id)->toBe($company->id);

    // Update
    Livewire::test('pages::dashboard.units')
        ->call('edit', $unit->id)
        ->assertSet('unitId', $unit->id)
        ->assertSet('showForm', true)
        ->set('title', 'شقة محدثة')
        ->set('description', 'وصف محدث')
        ->call('save');

    expect($unit->refresh()->title)->toBe('شقة محدثة');

    // Delete
    Livewire::test('pages::dashboard.units')
        ->call('delete', $unit->id);

    expect(Unit::count())->toBe(0);
});

it('prevents sales reps from creating, editing, or deleting units', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]); // default SalesRep
    actingAs($user);

    $unit = Unit::factory()->create(['company_id' => $company->id]);

    // Cannot open create form
    Livewire::test('pages::dashboard.units')
        ->call('openCreateForm')
        ->assertForbidden();

    // Cannot save
    Livewire::test('pages::dashboard.units')
        ->set('title', 'test')
        ->set('description', 'test')
        ->set('type', 'apartment')
        ->set('rooms', 1)
        ->set('area', 50)
        ->set('price', 500000)
        ->set('location', 'test')
        ->set('status', 'available')
        ->call('save')
        ->assertForbidden();

    // Cannot edit
    Livewire::test('pages::dashboard.units')
        ->call('edit', $unit->id)
        ->assertForbidden();

    // Cannot delete
    Livewire::test('pages::dashboard.units')
        ->call('delete', $unit->id)
        ->assertForbidden();
});

it('excludes non-available units from property search', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $unit = Unit::factory()->create([
        'company_id' => $company->id,
        'title' => 'شقة للبيع',
        'price' => 1000000,
        'status' => UnitStatus::Available,
    ]);

    $tool = new SearchPropertiesTool($company->id, $lead->id, '+201234567890');

    // Available unit appears in search
    $result = json_decode($tool->__invoke(2000000), true);
    expect($result)->toHaveCount(1)
        ->and($result[0]['title'])->toBe('شقة للبيع');

    // Reserved is excluded
    $unit->update(['status' => UnitStatus::Reserved]);
    expect($tool->__invoke(2000000))->toBe('لا توجد وحدات مطابقة للخيارات المدخلة حالياً.');

    // Sold is excluded
    $unit->update(['status' => UnitStatus::Sold]);
    expect($tool->__invoke(2000000))->toBe('لا توجد وحدات مطابقة للخيارات المدخلة حالياً.');
});

it('handles media uploads and reordering', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $unit = Unit::factory()->create(['company_id' => $company->id]);

    // Upload images
    $images = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
    ];

    Livewire::test('pages::dashboard.units')
        ->call('edit', $unit->id)
        ->set('newImages', $images)
        ->call('save');

    expect(UnitMedia::where('unit_id', $unit->id)->where('type', 'image')->count())->toBe(2);

    $mediaRecords = UnitMedia::where('unit_id', $unit->id)->where('type', 'image')->orderBy('sort_order')->get();
    expect($mediaRecords[0]->sort_order)->toBe(1)
        ->and($mediaRecords[1]->sort_order)->toBe(2);

    // Reorder
    $ids = $mediaRecords->pluck('id')->toArray();
    $reordered = [$ids[1], $ids[0]];

    Livewire::test('pages::dashboard.units')
        ->call('reorderMedia', $reordered);

    $mediaRecords = UnitMedia::where('unit_id', $unit->id)->where('type', 'image')->orderBy('sort_order')->get();
    expect($mediaRecords[0]->id)->toBe($ids[1])
        ->and($mediaRecords[0]->sort_order)->toBe(1)
        ->and($mediaRecords[1]->id)->toBe($ids[0])
        ->and($mediaRecords[1]->sort_order)->toBe(2);

    // Delete media — record at index 1 (= $ids[0]) after reorder
    $deletedPath = $mediaRecords[1]->path;

    Livewire::test('pages::dashboard.units')
        ->call('deleteMedia', $ids[0]);

    expect(UnitMedia::where('unit_id', $unit->id)->where('type', 'image')->count())->toBe(1);

    Storage::disk('public')->assertMissing($deletedPath);
    Storage::disk('public')->assertExists($mediaRecords[0]->path);
});
