<?php

namespace App\Services\Leads;

use App\Enums\LeadTier;
use App\Events\LeadBecameHot;
use App\Models\Lead;

class LeadScoringService
{
    protected array $signalPoints = [
        'stated_budget' => 30,
        'asked_price' => 25,
        'booked_visit' => 30,
        'asked_installment' => 15,
        'asked_media' => 10,
        'general_inquiry' => 5,
    ];

    public function applySignals(Lead $lead, array $signals): Lead
    {
        $scoredSignals = $lead->scored_signals ?? [];
        $score = $lead->score ?? 0;
        $previousTier = $lead->tier;

        foreach ($signals as $signal => $value) {
            if (! $value || ! isset($this->signalPoints[$signal])) {
                continue;
            }

            if (in_array($signal, $scoredSignals)) {
                continue;
            }

            $scoredSignals[] = $signal;
            $score += $this->signalPoints[$signal];
        }

        $score = min($score, 100);

        $tier = match (true) {
            $score >= 70 => LeadTier::Hot,
            $score >= 40 => LeadTier::Warm,
            default => LeadTier::Cold,
        };

        $lead->update([
            'score' => $score,
            'tier' => $tier,
            'scored_signals' => $scoredSignals,
        ]);

        if ($tier === LeadTier::Hot && $previousTier !== LeadTier::Hot) {
            LeadBecameHot::dispatch($lead->fresh());
        }

        return $lead->fresh();
    }
}
