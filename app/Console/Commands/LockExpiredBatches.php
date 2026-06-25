<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LockExpiredBatches extends Command
{
    protected $signature = 'batches:lock-expired';

    protected $description = 'Lock batches whose earmark date range has ended';

    public function handle(): void
    {
        $count = Batch::whereNull('is_locked')
            ->where('is_locked', false)
            ->whereNotNull('earmark_date_to')
            ->whereDate('earmark_date_to', '<', Carbon::now('Asia/Manila')->toDateString())
            ->update(['is_locked' => true]);

        $this->info(sprintf('Locked %s expired batch(es).', $count));
    }
}
