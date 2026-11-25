<?php

namespace App\Models;

use App\Services\SiteMedia;
use App\Support\HtmlCleaner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Post extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'status'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function imageUrl(): ?string
    {
        return SiteMedia::url($this->featured_image_path);
    }

    /** The excerpt if one was written, otherwise the beginning of the text. */
    public function excerptText(int $limit = 150): string
    {
        $text = filled($this->excerpt) ? HtmlCleaner::text($this->excerpt) : HtmlCleaner::text($this->content);

        return Str::limit($text, $limit);
    }
}
