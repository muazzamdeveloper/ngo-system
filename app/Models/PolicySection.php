<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicySection extends Model
{
    protected $fillable = ['policy_id', 'title', 'icon', 'content', 'sort_order', 'status'];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
