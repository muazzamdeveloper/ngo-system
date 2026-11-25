<?php

namespace App\Console\Commands;

use App\Models\LoginActivity;
use Illuminate\Console\Command;

class PruneLoginActivity extends Command
{
    protected $signature = 'app:prune-login-activity {--days=180 : Keep this many days of history (minimum 30)}';

    protected $description = 'Delete old login activity records';

    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));

        $deleted = LoginActivity::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Removed {$deleted} login records older than {$days} days.");

        return self::SUCCESS;
    }
}
