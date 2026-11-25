<?php

namespace App\Services;

use App\Exceptions\InvalidMemberTransition;
use App\Models\Member;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every member status change goes through this class, so the rules live in one place.
 *
 *   pending      -> approved (gets the membership number) | rejected
 *   rejected     -> pending (reopened)
 *   approved     -> deactivated | blocked
 *   deactivated  -> approved (restored)
 *   blocked      -> approved (unblocked)
 *
 * A membership number is given once, at the first approval, and never changes.
 * Deactivating or blocking switches the member's login off; restoring or
 * unblocking switches it back on.
 */
class MemberStatusService
{
    public function approve(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_PENDING], Member::STATUS_APPROVED, $note,
            loginActive: null, error: 'Only pending members can be approved.', assignNumber: true);
    }

    public function reject(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_PENDING], Member::STATUS_REJECTED, $note,
            loginActive: null, error: 'Only pending members can be rejected.');
    }

    public function reopen(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_REJECTED], Member::STATUS_PENDING, $note,
            loginActive: null, error: 'Only rejected members can be reopened.');
    }

    public function deactivate(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_APPROVED], Member::STATUS_DEACTIVATED, $note,
            loginActive: false, error: 'Only approved members can be deactivated.');
    }

    public function restore(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_DEACTIVATED], Member::STATUS_APPROVED, $note,
            loginActive: true, error: 'Only deactivated members can be restored.');
    }

    public function block(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_APPROVED], Member::STATUS_BLOCKED, $note,
            loginActive: false, error: 'Only approved members can be blocked.');
    }

    public function unblock(Member $member, User $actor, ?string $note = null): void
    {
        $this->transition($member, $actor, [Member::STATUS_BLOCKED], Member::STATUS_APPROVED, $note,
            loginActive: true, error: 'Only blocked members can be unblocked.');
    }

    /**
     * @param array<int, string> $from      statuses the member must currently have
     * @param bool|null          $loginActive null leaves the login account unchanged
     */
    private function transition(
        Member $member,
        User $actor,
        array $from,
        string $to,
        ?string $note,
        ?bool $loginActive,
        string $error,
        bool $assignNumber = false,
    ): void {
        DB::transaction(function () use ($member, $actor, $from, $to, $note, $loginActive, $error, $assignNumber) {
            // Re-read with a lock so two people acting at once cannot both succeed.
            $locked = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $from, true)) {
                throw new InvalidMemberTransition($error);
            }

            $locked->status = $to;
            $locked->status_note = $note;
            $locked->reviewed_by = $actor->id;
            $locked->reviewed_at = now();

            if ($assignNumber) {
                $locked->approved_at ??= now();   // first approval date, kept for good
            }

            if ($assignNumber && $locked->membership_number === null) {
                $locked->membership_number = $this->nextMembershipNumber();
            }

            $locked->save();

            if ($loginActive !== null && $locked->user) {
                $locked->user->forceFill(['is_active' => $loginActive])->save();
            }
        });

        $member->refresh();
    }

    /**
     * Next number such as LM-2026-0001. The sequence restarts every year and is
     * kept in its own locked table row so two approvals can never get the same number.
     * Must be called inside a database transaction.
     */
    private function nextMembershipNumber(): string
    {
        $year = (int) now()->year;

        DB::table('membership_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $last = (int) DB::table('membership_sequences')->where('year', $year)->lockForUpdate()->value('last_number');
        $prefix = OrganizationSetting::current()->membership_prefix;

        do {
            $last++;
            $number = sprintf('%s-%d-%04d', $prefix, $year, $last);
        } while (Member::where('membership_number', $number)->exists());

        DB::table('membership_sequences')->where('year', $year)->update(['last_number' => $last]);

        return $number;
    }
}
