<?php

namespace App\Models;

use App\Services\SiteMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeItem extends Model
{
    protected $fillable = ['title', 'subtitle', 'body', 'link_label', 'link_url', 'rating', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class, 'home_section_id');
    }

    public function imageUrl(): ?string
    {
        return SiteMedia::url($this->image_path);
    }
}
