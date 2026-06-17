<?php

namespace App\Listeners;

use App\Events\VisitBooked;
use App\Services\Leads\LeadScoringService;

class ApplyVisitSignal
{
    public function handle(VisitBooked $event): void
    {
        $visit = $event->visit;
        $lead = $visit->lead;

        if (! $lead) {
            return;
        }

        app(LeadScoringService::class)->applySignals($lead, ['booked_visit' => true]);
    }
}
