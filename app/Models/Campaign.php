<?php

namespace App\Models;

use App\Models\Concerns\HasAdminContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasAdminContent;

    protected $fillable = ['title', 'slug', 'short_description', 'description', 'goal_amount', 'status', 'start_date', 'end_date'];

    protected $slugSource = 'title';

    protected $appends = ['raised', 'progress'];

    protected function casts(): array
    {
        return ['goal_amount' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /** Money from completed donations only. */
    public function getRaisedAttribute(): float
    {
        return (float) $this->donations()->where('status', 'completed')->sum('amount');
    }

    public function getProgressAttribute(): int
    {
        $goal = (float) $this->goal_amount;

        return $goal > 0 ? (int) min(100, round($this->raised / $goal * 100)) : 0;
    }
}
