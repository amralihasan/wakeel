<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class RolloverBillingCyclesCommand extends Command
{
    protected $signature = 'billing:rollover';

    protected $description = 'Roll over billing cycles for companies with expired cycle dates and reset conversation counters';

    public function handle(): void
    {
        $now = now();

        $companies = Company::whereNotNull('billing_cycle_end')
            ->where('billing_cycle_end', '<=', $now)
            ->cursor();

        $count = 0;

        foreach ($companies as $company) {
            $company->rolloverBillingCycle();
            $count++;
        }

        $this->info("Rolled over {$count} billing cycle(s).");
    }
}
