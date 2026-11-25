<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared behaviour for the simple content modules (events, projects, gallery, ...).
 *
 * - status is 'active' or 'inactive'; the public site only shows active rows.
 * - a model with a $slugSource generates a unique slug from that column when none is given.
 */
trait HasAdminContent
{
    public const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.status', 'active');
    }

    protected static function bootHasAdminContent(): void
    {
        static::creating(function ($model) {
            $source = $model->slugSource ?? null;

            if ($source && blank($model->slug) && filled($model->{$source})) {
                $model->slug = static::uniqueSlug($model->{$source});
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title) ?: 'item', 180, '');
        $slug = $base;
        $n = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
