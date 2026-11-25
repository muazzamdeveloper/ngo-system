<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class DemoMembers extends Command
{
    private const DEMO_MARK = 'DEMO DATA';

    protected $signature = 'app:demo-members {count=25 : How many demo members to create} {--clear : Delete all demo members instead}';

    protected $description = 'Create (or remove with --clear) fake members for testing the admin panel';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Demo members cannot be created in production.');

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $deleted = Member::where('status_note', self::DEMO_MARK)->delete();
            $this->info("Removed {$deleted} demo members.");

            return self::SUCCESS;
        }

        $count = max(1, min((int) $this->argument('count'), 200));

        for ($i = 0; $i < $count; $i++) {
            $member = Member::factory()->withAadhaar()->make();
            $member->status = fake()->randomElement(Member::STATUSES);
            $member->status_note = self::DEMO_MARK;

            if ($member->status !== Member::STATUS_PENDING && $member->status !== Member::STATUS_REJECTED) {
                $member->membership_number = $this->nextDemoNumber();
                $member->approved_at = now()->subDays(random_int(0, 60));
            }

            $member->save();
        }

        $this->info("Created {$count} demo members. Remove them later with: php artisan app:demo-members --clear");

        return self::SUCCESS;
    }

    private function nextDemoNumber(): string
    {
        $next = Member::where('membership_number', 'like', 'DEMO-%')->count() + 1;

        do {
            $number = sprintf('DEMO-%04d', $next++);
        } while (Member::where('membership_number', $number)->exists());

        return $number;
    }
}
