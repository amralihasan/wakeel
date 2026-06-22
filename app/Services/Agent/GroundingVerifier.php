<?php

namespace App\Services\Agent;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\GroundingViolation;

class GroundingVerifier
{
    protected array $arabicToWestern = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public function __construct(
        protected RetrievedFacts $retrievedFacts,
    ) {}

    public function verify(string $text): ?string
    {
        $facts = $this->retrievedFacts;

        $normalized = $this->normalizeDigits($text);

        if ($facts->isEmpty()) {
            if ($this->containsNumericClaims($normalized)) {
                return 'Response contains numeric claims but no facts were retrieved from tools.';
            }

            return null;
        }

        $unitIds = $facts->unitIds();
        $validFacts = $facts->allNumericFacts();

        preg_match_all('/\b(\d+)\b/', $normalized, $matches);
        $mentionedNumbers = array_map('intval', $matches[1]);
        $mentionedNumbers = array_unique($mentionedNumbers);

        foreach ($mentionedNumbers as $num) {
            if (in_array($num, $unitIds, true)) {
                continue;
            }

            if ($this->isValidFact($num, $validFacts)) {
                continue;
            }

            if ($num < 100 && ! empty($unitIds)) {
                continue;
            }

            if ($num > 1000000000) {
                continue;
            }

            $isPhoneNumber = preg_match('/^01\d{8,9}$/', (string) $num);
            if ($isPhoneNumber) {
                continue;
            }

            return "Claimed numeric value {$num} is not present in any retrieved fact set.";
        }

        return null;
    }

    public function logViolation(
        Company $company,
        ?Conversation $conversation,
        ?string $modelUsed,
        string $originalText,
        ?string $safeFallbackText,
        string $actionTaken,
        ?string $violationReason,
    ): void {
        GroundingViolation::create([
            'company_id' => $company->id,
            'conversation_id' => $conversation?->id,
            'model_used' => $modelUsed,
            'original_text' => $originalText,
            'safe_fallback_text' => $safeFallbackText,
            'action_taken' => $actionTaken,
            'violation_reason' => $violationReason,
            'retrieved_facts' => $this->retrievedFacts->units(),
        ]);
    }

    public function checkBeforeDispatch(string $text, Company $company, ?Conversation $conversation, ?string $modelUsed): ?string
    {
        $violation = $this->verify($text);

        if ($violation !== null) {
            $this->logViolation(
                company: $company,
                conversation: $conversation,
                modelUsed: $modelUsed,
                originalText: $text,
                safeFallbackText: null,
                actionTaken: 'blocked',
                violationReason: $violation,
            );
        }

        return $violation;
    }

    public function normalizeDigits(string $text): string
    {
        $text = str_replace(
            array_keys($this->arabicToWestern),
            array_values($this->arabicToWestern),
            $text
        );

        return str_replace(',', '', $text);
    }

    protected function containsNumericClaims(string $text): bool
    {
        if (str_contains($text, 'ماعنديش') || str_contains($text, 'غير متوف') || str_contains($text, 'أوصّلك') || str_contains($text, 'مستشار')) {
            return false;
        }

        return str_contains($text, 'جنيه') || preg_match('/\d{4,}/', $text);
    }

    protected function isValidFact(int $number, array $validFacts): bool
    {
        foreach ($validFacts as $fact) {
            $tolerance = max(1, (int) ($fact * 0.01));

            if (abs($number - $fact) <= $tolerance) {
                return true;
            }
        }

        return false;
    }
}
