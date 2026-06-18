<?php

use App\Exceptions\WhatsAppException;
use App\Jobs\SendWhatsAppMedia;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\WhatsAppChannel;
use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\swap;

beforeEach(function () {
    config()->set('services.dialog360', [
        'base_url' => 'https://waba-v2.360dialog.io',
        'api_key' => 'test-api-key',
        'channel_pool' => ['channel-1', 'channel-2'],
    ]);
});

it('sends a text message', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response(['messages' => [['id' => 'msg123']]], 200),
    ]);

    $client = app(WhatsAppClientContract::class);
    $messageId = $client->sendText('ch1', '+201234567890', 'Hello world');

    expect($messageId)->toBeJson()
        ->and(json_decode($messageId, true)['messages'][0]['id'])->toBe('msg123');
});

it('sends an image message', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response(['messages' => [['id' => 'img456']]], 200),
    ]);

    $client = app(WhatsAppClientContract::class);
    $response = $client->sendImage('ch1', '+201234567890', 'https://example.com/photo.jpg', 'Check this out');

    expect(json_decode($response, true)['messages'][0]['id'])->toBe('img456');
});

it('sends a document message', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response(['messages' => [['id' => 'doc789']]], 200),
    ]);

    $client = app(WhatsAppClientContract::class);
    $response = $client->sendDocument('ch1', '+201234567890', 'https://example.com/doc.pdf', 'brochure.pdf');

    expect(json_decode($response, true)['messages'][0]['id'])->toBe('doc789');
});

it('sends interactive buttons', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response(['messages' => [['id' => 'btn001']]], 200),
    ]);

    $client = app(WhatsAppClientContract::class);
    $response = $client->sendInteractiveButtons('ch1', '+201234567890', 'Choose an option', [
        ['type' => 'reply', 'reply' => ['id' => 'opt1', 'title' => 'Yes']],
    ]);

    expect(json_decode($response, true)['messages'][0]['id'])->toBe('btn001');
});

it('sends an interactive list', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response(['messages' => [['id' => 'lst002']]], 200),
    ]);

    $client = app(WhatsAppClientContract::class);
    $response = $client->sendInteractiveList('ch1', '+201234567890', 'Pick a category', [
        ['title' => 'Category A', 'rows' => [['id' => 'a1', 'title' => 'Item A1']]],
    ]);

    expect(json_decode($response, true)['messages'][0]['id'])->toBe('lst002');
});

it('sends a location message', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response(['messages' => [['id' => 'loc003']]], 200),
    ]);

    $client = app(WhatsAppClientContract::class);
    $response = $client->sendLocation('ch1', '+201234567890', 30.0444, 31.2357, 'Cairo');

    expect(json_decode($response, true)['messages'][0]['id'])->toBe('loc003');
});

it('throws WhatsAppException on non-2xx response', function () {
    Http::fake([
        'waba-v2.360dialog.io/ch1/messages' => Http::response('Unauthorized', 401),
    ]);

    $client = app(WhatsAppClientContract::class);

    expect(fn () => $client->sendText('ch1', '+201234567890', 'Hello'))
        ->toThrow(WhatsAppException::class, '401');
});

it('assigns a number from the pool to a company', function () {
    Http::fake([
        'waba-v2.360dialog.io/configs/webhook' => Http::response('OK', 200),
    ]);

    WhatsAppChannel::create(['number' => 'channel-1', 'channel_id' => 'channel-1', 'status' => 'available']);
    WhatsAppChannel::create(['number' => 'channel-2', 'channel_id' => 'channel-2', 'status' => 'available']);

    $company = Company::factory()->create();

    $client = app(WhatsAppClientContract::class);
    $client->assignNumberFromPool($company);

    $company->refresh();

    expect($company->dialog360_channel_id)->not->toBeNull()
        ->and($company->whatsapp_number)->not->toBeNull()
        ->and(in_array($company->dialog360_channel_id, ['channel-1', 'channel-2']))->toBeTrue();
});

it('dispatches SendWhatsAppText job to the queue', function () {
    Queue::fake();

    SendWhatsAppText::dispatch('ch1', '+201234567890', 'Queued message');

    Queue::assertPushed(SendWhatsAppText::class, function ($job) {
        return $job->channelId === 'ch1'
            && $job->to === '+201234567890'
            && $job->body === 'Queued message';
    });
});

it('dispatches SendWhatsAppMedia job to the queue', function () {
    Queue::fake();

    SendWhatsAppMedia::dispatch('ch1', '+201234567890', 'https://example.com/photo.jpg', 'image', null, 'A photo');

    Queue::assertPushed(SendWhatsAppMedia::class, function ($job) {
        return $job->channelId === 'ch1'
            && $job->to === '+201234567890'
            && $job->type === 'image'
            && $job->caption === 'A photo';
    });
});

it('SendWhatsAppText job calls the client on handle', function () {
    $mock = Mockery::mock(WhatsAppClientContract::class);
    $mock->shouldReceive('sendText')
        ->once()
        ->with('ch1', '+201234567890', 'Hello');

    swap(WhatsAppClientContract::class, $mock);

    (new SendWhatsAppText('ch1', '+201234567890', 'Hello'))->handle(app(WhatsAppClientContract::class));
});

it('throws WhatsAppException when the channel pool is empty', function () {
    WhatsAppChannel::query()->delete();

    $company = Company::factory()->create();
    $client = app(WhatsAppClientContract::class);

    expect(fn () => $client->assignNumberFromPool($company))
        ->toThrow(WhatsAppException::class, 'No channel IDs available');
});
