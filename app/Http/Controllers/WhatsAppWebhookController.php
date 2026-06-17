<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundMessageJob;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.dialog360.verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        $channelId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
        $from = $payload['entry'][0]['changes'][0]['value']['messages'][0]['from'] ?? null;
        $waMessageId = $payload['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? null;

        if (! $channelId || ! $from || ! $waMessageId) {
            return response()->json(['status' => 'ignored'], 200);
        }

        if (Message::where('wa_message_id', $waMessageId)->exists()) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        $company = Company::where('dialog360_channel_id', $channelId)->first();

        if (! $company) {
            return response()->json(['status' => 'unknown_company'], 200);
        }

        $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];
        $body = $this->extractBody($messageData);

        $lead = Lead::firstOrCreate(
            ['company_id' => $company->id, 'customer_phone' => $from],
            ['name' => null, 'status' => 'new', 'source' => 'whatsapp'],
        );

        $conversation = Conversation::firstOrCreate(
            ['company_id' => $company->id, 'customer_phone' => $from],
            ['lead_id' => $lead->id, 'mode' => 'bot'],
        );

        if ($conversation->lead_id !== $lead->id) {
            $conversation->update(['lead_id' => $lead->id]);
        }

        $conversation->touch();

        Message::create([
            'company_id' => $company->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'sender' => 'customer',
            'body' => $body,
            'wa_message_id' => $waMessageId,
            'created_at' => now(),
        ]);

        $session = app(ConversationSession::class);
        $session->setCompany($company);
        $session->setPhone($from);
        $session->pushTurn(['role' => 'user', 'content' => $body]);

        ProcessInboundMessageJob::dispatch($company->id, $from, $body);

        return response()->json(['status' => 'ok'], 200);
    }

    protected function extractBody(array $messageData): ?string
    {
        if (isset($messageData['text']['body'])) {
            return $messageData['text']['body'];
        }

        if (isset($messageData['image']['link'])) {
            return $messageData['image']['link'];
        }

        if (isset($messageData['document']['link'])) {
            return $messageData['document']['link'];
        }

        return null;
    }
}
