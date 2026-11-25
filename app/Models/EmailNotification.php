<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailNotification extends Model
{
    public const CATEGORIES = [
        'announcement' => 'Announcement',
        'important' => 'Important notice',
        'success' => 'Success / merit',
        'event' => 'Event / meeting',
        'urgent' => 'Urgent alert',
    ];

    public const AUDIENCES = [
        'approved_members' => 'All approved members',
        'pending_members' => 'All pending applicants',
    ];

    protected $fillable = ['subject', 'body', 'category', 'audience', 'action_url', 'via_smtp', 'recipients', 'sent_count', 'failed_count', 'sent_by'];

    protected function casts(): array
    {
        return ['via_smtp' => 'boolean'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
