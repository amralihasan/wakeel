<?php

namespace App\Services\Agent;

class RetrievedFacts
{
    protected array $units = [];

    protected array $installmentResults = [];

    public function addUnit(int $unitId, array $data): void
    {
        $this->units[$unitId] = $data;
    }

    public function addInstallmentResult(float $monthlyPayment): void
    {
        $this->installmentResults[] = $monthlyPayment;
    }

    public function units(): array
    {
        return $this->units;
    }

    public function installmentResults(): array
    {
        return $this->installmentResults;
    }

    public function allNumericFacts(): array
    {
        $facts = [];

        foreach ($this->units as $unit) {
            if (isset($unit['price'])) {
                $facts[] = (int) $unit['price'];
            }
            if (isset($unit['area'])) {
                $facts[] = (int) $unit['area'];
            }
            if (isset($unit['rooms'])) {
                $facts[] = (int) $unit['rooms'];
            }
            if (isset($unit['down_payment'])) {
                $facts[] = (int) $unit['down_payment'];
            }
            if (isset($unit['installment_years'])) {
                $facts[] = (int) $unit['installment_years'];
            }
        }

        foreach ($this->installmentResults as $payment) {
            $facts[] = (int) round($payment);
        }

        return $facts;
    }

    public function unitIds(): array
    {
        return array_keys($this->units);
    }

    public function isEmpty(): bool
    {
        return empty($this->units);
    }

    public function clear(): void
    {
        $this->units = [];
        $this->installmentResults = [];
    }
}
