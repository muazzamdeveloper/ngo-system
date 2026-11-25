<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Volunteer extends Model
{
    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    protected $fillable = ['full_name', 'email', 'mobile', 'city', 'state', 'interests', 'message', 'source'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
