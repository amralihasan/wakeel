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

class ProcessInboundMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

            app(AgentRunner::class)->handle($company, $this->customerPhone, $this->body ?? '');
        });
    }
}
