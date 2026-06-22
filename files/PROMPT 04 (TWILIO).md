# PROMPT 04 (TWILIO) — WhatsApp via Twilio (default provider, 360dialog removed)

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> **This REPLACES the 360dialog-based prompt 04** and updates the integration points in
> prompts 05, 12, 14, and 16. Twilio is now the **default and only** WhatsApp provider.
> We keep a clean provider abstraction so a different BSP could be added later, but
> 360dialog code is removed.

> **Update the master context:**
> - §2 WhatsApp provider row → **Twilio (WhatsApp Business API), default**; remove
>   360dialog.
> - §10 env → replace the `DIALOG360_*` keys with the Twilio keys below.
> - §3 / §5 webhook notes → Twilio posts **form-encoded** params and is verified via
>   **`X-Twilio-Signature`** (not Meta's JSON webhook + verify-token handshake).

---

**Goal:** All WhatsApp messaging (send + receive) runs through **Twilio**. A clean
`WhatsAppProviderContract` defines what the app needs; `TwilioWhatsAppProvider`
implements it. Everything else (webhook, agent, tools, number provisioning, templates,
admin) talks to the contract, never to Twilio directly — so the agent core is untouched
by the provider swap.

---

## A. Remove 360dialog

1. Delete the `Dialog360Client`, its binding, and any 360dialog config/env
   (`DIALOG360_*` in `.env`, `.env.example`, `config/services.php`).
2. Remove the Meta-style webhook **verify handshake** (the `GET /webhook/whatsapp`
   `hub.challenge` logic) — Twilio doesn't use it. Keep the route only if needed for a
   health ping.
3. Grep for `dialog360` / `360dialog` and remove all references. Run the suite to
   confirm nothing depends on it.

---

## B. Twilio setup & env

1. Install the official **Twilio PHP SDK** (`composer require twilio/sdk`).
2. Env keys (replace the 360dialog keys in master-context §10):
   ```
   WHATSAPP_PROVIDER=twilio
   TWILIO_ACCOUNT_SID=
   TWILIO_AUTH_TOKEN=
   TWILIO_MESSAGING_SERVICE_SID=        # optional: groups senders, enables scaling
   TWILIO_WHATSAPP_FROM=                # e.g. whatsapp:+14155238886 (sender / sandbox)
   TWILIO_STATUS_CALLBACK_URL=          # delivery/status webhook (optional)
   ```
3. Config in `config/services.php` → `twilio` (sid, token, messaging service sid,
   default from, status callback). Never log the auth token.

---

## C. The provider abstraction

1. Define `app/Services/WhatsApp/WhatsAppProviderContract.php` with the operations the
   app needs (provider-neutral; addresses are bare E.164 phone numbers — the provider
   adds any `whatsapp:` prefixing internally):
   - `sendText(string $from, string $to, string $body): string`  (returns provider msg id)
   - `sendImage($from, $to, string $mediaUrl, ?string $caption): string`
   - `sendDocument($from, $to, string $mediaUrl, ?string $filename, ?string $caption): string`
   - `sendTemplate($from, $to, string $templateRef, array $variables): string`
     (approved template / Twilio Content — for outside the 24h window)
   - `sendInteractiveButtons($from, $to, string $body, array $buttons): string`
   - `sendInteractiveList($from, $to, string $body, array $sections): string`
   - `sendLocation($from, $to, float $lat, float $lng, ?string $name): string`
   - `validateInboundSignature(Request $request): bool`
   - `parseInbound(Request $request): InboundMessage`  (normalize to our own DTO)
2. Bind the implementation by `WHATSAPP_PROVIDER` config (so it's swappable later).

---

## D. `TwilioWhatsAppProvider` (the implementation)

1. **Sending** via the Twilio Messages API. `From` = the company's WhatsApp sender
   (`whatsapp:+...`) or the Messaging Service SID; `To` = `whatsapp:{customer}`. Text via
   `body`; media via the `mediaUrl` param (URL must be **publicly reachable** — our S3/
   MinIO unit-media URLs from prompt 11). Return the Twilio `MessageSid`.
2. **Interactive messages & templates — important difference:** Twilio delivers WhatsApp
   buttons/lists and out-of-session templates through the **Content API (Content
   Templates)**, not ad-hoc interactive payloads like 360dialog. Implement:
   - `sendInteractiveButtons` / `sendInteractiveList` → send a pre-created **quick-reply
     / list-picker Content Template** by its Content SID with variables. (For MVP, if a
     given interactive type isn't set up as Content yet, **degrade gracefully to a plain
     numbered-text message** so the agent still works — document this.)
   - `sendTemplate` → send an **approved Content Template** by reference (Content SID) +
     variables. These are required outside the 24-hour customer-service window
     (master-context / prompt 14).
3. **Inbound parsing** — Twilio POSTs `application/x-www-form-urlencoded`. Map fields to
   our `InboundMessage` DTO:
   - `From` (`whatsapp:+20…`) → customer phone, `To` (`whatsapp:+…`) → our sender,
     `Body` → text, `MessageSid` → provider id (dedupe key), `ProfileName`/`WaId` →
     contact info, `NumMedia` + `MediaUrl{n}` + `MediaContentType{n}` → media. Strip the
     `whatsapp:` prefix to bare E.164 everywhere internally.
   - Inbound **media URLs from Twilio require auth to fetch** — when storing/forwarding
     inbound media, fetch with the account credentials, then persist to our storage.
4. **Signature validation** — `validateInboundSignature` uses Twilio's
   `RequestValidator` with the auth token, the **exact full request URL** (mind proxy/
   ngrok scheme + host), and the POST params. Reject requests that fail validation.
5. Typed `WhatsAppException` on API errors; HTTP retries/timeouts via the SDK or wrapping
   client; sanitized logging.

---

## E. Sender / number provisioning (replaces 360dialog channel pool)

1. Model B (platform supplies the number) still holds, but the unit is now a **Twilio
   WhatsApp sender**. Update the `whatsapp_channels` concept (prompt 16) to store Twilio
   senders: `phone_number` (E.164), `twilio_sender` (`whatsapp:+…` or sender SID),
   `messaging_service_sid` (nullable), status (available/assigned/suspended/retired),
   `assigned_company_id`.
2. `assignNumberFromPool(Company $company)` → pick an available Twilio sender, store it on
   the company (`whatsapp_number` + the Twilio sender reference), and ensure inbound for
   that sender routes to our webhook (configured on the Twilio sender / Messaging
   Service). For MVP the senders can be pre-registered in Twilio and listed in the pool;
   document the manual Twilio-side onboarding steps.
3. Resolve the owning `Company` on inbound by matching the `To` sender → company
   (replaces resolving by `dialog360_channel_id`).

---

## F. Outbound jobs (unchanged contract, Twilio under the hood)

Keep the queued send jobs (`SendWhatsAppText`, `SendWhatsAppMedia`, plus a
`SendWhatsAppTemplate`) — they now call the **contract**, which Twilio implements. The
agent and tools (prompts 07/08) are **unchanged**: they still dispatch these jobs and
never know the provider is Twilio.

---

## G. Integration-point updates (ripple changes)

- **Prompt 05 (webhook):** the `POST /webhook/whatsapp` controller now (1) validates the
  Twilio signature, (2) calls `provider->parseInbound()`, (3) resolves the company by the
  `To` sender, (4) dedupes on `MessageSid`, (5) dispatches `ProcessInboundMessageJob`,
  (6) returns the appropriate Twilio response (200/empty TwiML). Remove the verify
  handshake. The Redis `mode` gate is unchanged.
- **Prompt 12 (onboarding):** number assignment now shows the Twilio-provided number;
  the `wa.me` link + QR still work off the bare E.164.
- **Prompt 14 (follow-ups):** out-of-session messages use Twilio **Content Templates**
  (approved), selected by the lead's locale (ar/en).
- **Prompt 16 (admin):** the channels resource manages **Twilio senders**; health = last
  inbound seen + signature-valid; show Messaging Service grouping if used.
- **Prompt 17 (deploy):** document Twilio account setup, sender registration, webhook URL
  on the sender/Messaging Service, and the WhatsApp **Sandbox** for dev testing.

---

## H. Dev/testing note

For local testing, use the **Twilio WhatsApp Sandbox**: join it from your phone, set the
sandbox inbound webhook to your public tunnel (e.g. `ngrok` → `/webhook/whatsapp`), and
send/receive against the sandbox number before provisioning real senders.

---

**Tests (fake the Twilio SDK / HTTP):**
- 360dialog fully removed (no references; suite green).
- Each send method targets the Messages/Content API with correct `From`/`To`
  (`whatsapp:` prefixing) and payload shape; returns the `MessageSid`.
- `validateInboundSignature` accepts a correctly-signed Twilio request and rejects a
  tampered one (correct URL + params + token).
- `parseInbound` maps a real Twilio form payload (text and a media example with
  `NumMedia`/`MediaUrl0`) to our `InboundMessage` DTO, stripping `whatsapp:` to E.164.
- Company resolves by the inbound `To` sender; unknown sender is handled safely.
- Interactive degrade-path: when no Content Template exists, buttons/list fall back to a
  numbered-text message.
- `sendTemplate` sends an approved Content Template for an out-of-session follow-up.
- `assignNumberFromPool` assigns a Twilio sender and routes inbound to the company.

**Done when:** all WhatsApp send/receive runs through Twilio behind
`WhatsAppProviderContract`, signatures are verified, inbound is parsed and routed to the
right tenant, templates/interactive work (with graceful fallback), the number pool holds
Twilio senders, and the agent core remains provider-agnostic and untouched.
