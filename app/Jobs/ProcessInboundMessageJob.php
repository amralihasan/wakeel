<?php

namespace App\Jobs;

use App\Events\InboundMessageReceived;
use App\Models\Company;
use App\Services\Agent\AgentRunner;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessInboundMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $companyId,
        public string $customerPhone,
        public ?string $body
    ) {}

    public function handle(): void
    {
        $company = Company::find($this->companyId);

        if (! $company) {
            return;
        }

        $lock = Cache::lock("process:{$this->companyId}:{$this->customerPhone}", 10);

        $lock->get(function () use ($company) {
            $session = app(ConversationSession::class);
            $session->setCompany($company);
            $session->setPhone($this->customerPhone);

            $mode = $session->getMode();

            if (in_array($mode, ['human', 'pending_handoff'])) {
                InboundMessageReceived::dispatch($company->id, $this->customerPhone, $this->body);

                return;
            }

            $botSettings = $company->bot_settings ?? [];
            if (! ($botSettings['active'] ?? true)) {
                return;
            }

            app(AgentRunner::class)->handle($company, $this->customerPhone, $this->body ?? '');
        });
    }

    public function failed(\Throwable $exception): void
    {
        $masked = $this->maskPhone($this->customerPhone);

        Log::error('ProcessInboundMessageJob failed', [
            'company_id' => $this->companyId,
            'customer_phone' => $masked,
            'error' => $exception->getMessage(),
        ]);
    }

    protected function maskPhone(string $phone): string
    {
        $length = strlen($phone);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        $visible = 4;
        $masked = $length - $visible - 3;

        return substr($phone, 0, $visible).str_repeat('*', $masked).substr($phone, -3);
    }
}
