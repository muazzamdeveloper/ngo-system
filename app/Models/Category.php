<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    public const DEFAULT_SLUG = 'uncategorized';

    protected $fillable = ['name', 'slug', 'description'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function isDefault(): bool
    {
        return $this->slug === self::DEFAULT_SLUG;
    }

    public static function uncategorized(): self
    {
        return static::firstOrCreate(['slug' => self::DEFAULT_SLUG], ['name' => 'Uncategorized']);
    }

    /** Find the category with this name, or create it (like typing a new category in WordPress). */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);
        $slug = Str::slug($name) ?: 'category';

        return static::firstOrCreate(['slug' => $slug], ['name' => mb_substr($name, 0, 60)]);
    }
}
